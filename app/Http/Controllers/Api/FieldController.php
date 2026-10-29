<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseRequest;
use App\Models\CaseEvent;
use App\Models\CaseEvidence;
use App\Models\CaseFollowup;
use App\Models\CivicCase;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\CaseActionNotification;
use App\Services\CaseEvidenceService;
use App\Services\CaseSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
class FieldController extends Controller {
 private function guard(Request $r):User {
  $u=$r->user();
  abort_unless($u instanceof User && $u->is_active && $u->role==='focal' &&
   !$u->must_change_password && $u->tokenCan('field:report'),403);
  return $u;
 }
 private function tokenResponse(User $user,string $deviceName):\Illuminate\Http\JsonResponse {
  $expires=now()->addDays(30);
  $token=$user->createToken('field-'.substr($deviceName,0,40),['field:report'],$expires);
  return response()->json(['token'=>$token->plainTextToken,'expires_at'=>$expires->toIso8601String(),
   'user'=>['id'=>$user->id,'name'=>$user->name,'role'=>$user->role]])
   ->header('Cache-Control','private, no-store');
 }
 private function fieldEligible(User $user):bool {
  return (bool)$user->is_active && $user->role==='focal' && !$user->must_change_password;
 }
 public function login(Request $r){
  $data=$r->validate(['email'=>['required','email'],'password'=>['required','string'],
   'device_name'=>['nullable','string','max:80']]);
  $user=User::where('email',$data['email'])->first();
  if(!$user || !Hash::check($data['password'],$user->password) ||
   !$user->is_active || $user->role!=='focal')
   throw ValidationException::withMessages(['email'=>'Invalid credentials or ineligible field account.']);
  if($user->must_change_password)
   return response()->json(['message'=>'Change your initial password in the web portal before using the Android app.'],403)
    ->header('Cache-Control','private, no-store');
  if($user->two_factor_confirmed_at && $user->two_factor_secret){
   $challenge=Str::random(64);
   $cacheKey='field-login-challenge:'.hash('sha256',$challenge);
   Cache::put($cacheKey,['user_id'=>$user->id,'attempts'=>0,
    'expires_at'=>now()->addMinutes(5)->timestamp,
    'device_name'=>$data['device_name']??'android'],now()->addMinutes(5));
   return response()->json(['two_factor_required'=>true,'challenge'=>$challenge,
    'expires_in'=>300],202)->header('Cache-Control','private, no-store');
  }
  return $this->tokenResponse($user,$data['device_name']??'android');
 }
 public function verifyTwoFactor(Request $r){
  $data=$r->validate(['challenge'=>['required','string','size:64'],
   'otp'=>['required','digits:6']]);
  $hash=hash('sha256',$data['challenge']);
  $cacheKey='field-login-challenge:'.$hash;
  return Cache::lock('field-login-lock:'.$hash,5)->block(2,function()use($cacheKey,$data){
   $state=Cache::get($cacheKey);
   if(!is_array($state) || ($state['expires_at']??0)<=time())
    throw ValidationException::withMessages(['otp'=>'Verification expired. Sign in again.']);
   $user=User::find($state['user_id']??0);
   if(!$user || !$this->fieldEligible($user) || !$user->two_factor_confirmed_at || !$user->two_factor_secret){
    Cache::forget($cacheKey);
    throw ValidationException::withMessages(['otp'=>'Verification unavailable. Sign in again.']);
   }
   if(!(new Google2FA())->verifyKey($user->two_factor_secret,$data['otp'],1)){
    $state['attempts']=($state['attempts']??0)+1;
    if($state['attempts']>=5)Cache::forget($cacheKey);
    else Cache::put($cacheKey,$state,now()->addSeconds(max(1,$state['expires_at']-time())));
    throw ValidationException::withMessages(['otp'=>$state['attempts']>=5?
     'Too many attempts. Sign in again.':'Invalid authenticator code.']);
   }
   Cache::forget($cacheKey);
   return $this->tokenResponse($user,$state['device_name']??'android');
  });
 }
 public function logout(Request $r){
  $this->guard($r);
  $r->user()->currentAccessToken()?->delete();
  return response()->json(['message'=>'Signed out.']);
 }
 public function bootstrap(Request $r){
  $u=$this->guard($r);
  return response()->json(['user'=>['id'=>$u->id,'name'=>$u->name,'role'=>$u->role],
   'lgas'=>$u->assignedLgas()->orderBy('lgas.name')->get(['lgas.id','lgas.name']),
   'sectors'=>Sector::where('is_active',true)->orderBy('name')->get(['id','name']),
   'categories'=>\App\Models\ReportCategory::where('is_active',true)->get(['id','type','name']),
   'communities'=>\App\Models\Community::where('is_active',true)
     ->whereIn('lga_id',$u->assignedLgas()->pluck('lgas.id'))->get(['id','lga_id','name']),
   'status_labels'=>\App\Models\WorkflowStatusLabel::get(['kind','code','label']),
   'max_evidence_files'=>5,'max_evidence_bytes'=>5242880,'server_time'=>now()->toIso8601String()])
   ->header('Cache-Control','private, no-store');
 }
 public function index(Request $r){
  $u=$this->guard($r);
  $rows=CivicCase::where('reporter_id',$u->id)->with('lga:id,name')
   ->latest('id')->limit(100)->get(['id','reference','title','lga_id','verification_status','progress_status','observation_date','created_at']);
  return response()->json(['reports'=>$rows])->header('Cache-Control','private, no-store');
 }
 public function show(Request $r,CivicCase $case){
  $u=$this->guard($r);
  abort_unless((int)$case->reporter_id===(int)$u->id,403);
  $case->load(['lga:id,name','sector:id,name','followups:id,case_id,author_id,note,created_at',
   'evidence:id,case_id,original_name,mime_type,size_bytes']);
  $fields=$case->only(['type','title','description','lga_id','community','sector_id','report_category_id',
    'observations','duty_bearers','progress_status','latitude','longitude']);
  $fields['observation_date']=$case->observation_date->format('Y-m-d');
  return response()->json(['report'=>$case->only(['id','reference','title','description','verification_status','progress_status',
   'observation_date','community','latitude','longitude','lga','sector','followups','evidence','closure_status']),
   'editable'=>$u->can('update',$case),
   'version'=>(int)\App\Models\CaseRevision::where('case_id',$case->id)->max('version'),
   'fields'=>$fields])
   ->header('Cache-Control','private, no-store');
 }
 public function store(StoreCaseRequest $r,CaseSubmissionService $service,CaseEvidenceService $evidenceService){
  $u=$this->guard($r);
  $data=$r->validated();
  unset($data['evidence']);
  $case=$service->submit($u,$data);
  if($r->hasFile('evidence'))$evidenceService->attach($case,$u,$r->file('evidence'));
  return response()->json(['id'=>$case->id,'reference'=>$case->reference,
   'verification_status'=>$case->verification_status],201)
   ->header('Cache-Control','private, no-store');
 }
 public function similar(Request $r,\App\Services\SimilarReportService $service){
  $user=$this->guard($r);
  $data=$r->validate([
   'title'=>['required','string','min:5','max:200'],
   'lga_id'=>['required','integer','exists:lgas,id'],
   'community'=>['nullable','string','max:150'],
   'sector_id'=>['nullable','integer','exists:sectors,id'],
   'type'=>['nullable','in:public_project,governance_issue'],
   'observation_date'=>['nullable','date','before_or_equal:today'],
  ]);
  abort_unless($user->assignedLgas()->where('lgas.id',$data['lga_id'])->exists(),403);
  return response()->json(['matches'=>$service->find($user,$data)])
    ->header('Cache-Control','private, no-store');
 }
 public function clarification(Request $r,CivicCase $case){
  $u=$this->guard($r);
  abort_unless((int)$case->reporter_id===(int)$u->id,403);
  $data=$r->validate(['response'=>['required','string','min:5','max:5000']]);
  DB::transaction(function()use($r,$case,$data){
   $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
   abort_if($locked->closure_status==='closed',403,'Closed case.');
   if($locked->verification_status!=='clarification_requested')
    throw ValidationException::withMessages(['response'=>'No clarification request is open.']);
   CaseFollowup::create(['case_id'=>$locked->id,'author_id'=>$r->user()->id,'note'=>$data['response']]);
   $locked->verification_status='under_review';$locked->save();
   CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,
    'event_type'=>'clarification_response','from_status'=>'clarification_requested',
    'to_status'=>'under_review','comment'=>$data['response']]);
   if($locked->reviewer?->is_active)
    $locked->reviewer->notify(new CaseActionNotification($locked->id,$locked->reference,
      'Field clarification received','The focal person submitted a response.'));
  });
  return response()->json(['message'=>'Clarification received.'])->header('Cache-Control','private, no-store');
 }
 public function evidence(Request $r,CaseEvidence $evidence){
  $u=$this->guard($r);
  $case=CivicCase::findOrFail($evidence->case_id);
  abort_unless((int)$case->reporter_id===(int)$u->id,403);
  abort_unless(Storage::disk($evidence->disk)->exists($evidence->path),404);
  return Storage::disk($evidence->disk)->download($evidence->path,$evidence->original_name,
   ['Cache-Control'=>'private, no-store']);
 }
}
