<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\CaseRevision;
use App\Models\CaseEvent;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class CaseRevisionController extends Controller {
 private const FIELDS=['type','title','description','lga_id','community','sector_id','report_category_id','observation_date','observations','duty_bearers','latitude','longitude'];
 public function edit(Request $r,CivicCase $case){
  $r->user()->can('update',$case)||abort(403);
  $lgas=$r->user()->role==='focal'?$r->user()->assignedLgas()->orderBy('lgas.name')->get():Lga::orderBy('name')->get();
  return view('cases.edit',['case'=>$case,'lgas'=>$lgas,'sectors'=>Sector::where('is_active',true)->orWhere('id',$case->sector_id)->orderBy('name')->get(),
     'categories'=>\App\Models\ReportCategory::where('is_active',true)->orWhere('id',$case->report_category_id)->orderBy('name')->get(),
     'communities'=>\App\Models\Community::whereIn('lga_id',$lgas->pluck('id'))->where('is_active',true)->orderBy('name')->get()]);
 }
 public function update(Request $r,CivicCase $case){
  $this->applyRevision($r,$case);
  return redirect()->route('cases.show',$case)->with('message','Report changes saved with version history.');
 }
 public function apiUpdate(Request $r,CivicCase $case){
  $user=$r->user();
  abort_unless($user instanceof \App\Models\User && $user->is_active &&
      $user->role==='focal' && !$user->must_change_password &&
      $user->tokenCan('field:report') && (int)$case->reporter_id===(int)$user->id,403);
  $this->applyRevision($r,$case);
  return response()->json([
   'message'=>'Report revision saved.',
   'version'=>(int)CaseRevision::where('case_id',$case->id)->max('version'),
   'reference'=>$case->reference,
  ])->header('Cache-Control','private, no-store');
 }
 private function applyRevision(Request $r,CivicCase $case):void{

  $r->user()->can('update',$case)||abort(403);
  $data=$r->validate([
   'version'=>['required','integer','min:0'],
   'reason'=>['required','string','min:5','max:2000'],
   'type'=>['required',Rule::in(['public_project','governance_issue'])],
   'title'=>['required','string','min:5','max:200'],
   'description'=>['required','string','min:15','max:10000'],
   'lga_id'=>['required','integer','exists:lgas,id',function($field,$value,$fail)use($r){
     if($r->user()->role!=='focal')return;
     if(!$r->user()->assignedLgas()->where('lgas.id',$value)->exists())$fail('This LGA is not assigned to your account.');
   }],
   'community'=>['required','string','max:150'],
   'sector_id'=>['required','integer','exists:sectors,id'],
   'report_category_id'=>['nullable','integer',\Illuminate\Validation\Rule::exists('report_categories','id')
      ->where('is_active',true)->where('type',$r->input('type'))],
   'observation_date'=>['required','date','before_or_equal:today'],
   'observations'=>['nullable','string','max:10000'],
   'duty_bearers'=>['nullable','string','max:5000'],
   'latitude'=>['nullable','required_with:longitude','numeric','between:-90,90'],
   'longitude'=>['nullable','required_with:latitude','numeric','between:-180,180']
  ]);
  DB::transaction(function()use($r,$case,$data){
   $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
   if(!$r->user()->can('update',$locked)||$locked->closure_status==='closed')abort(403,'Report is locked for editing.');
   $current=CaseRevision::where('case_id',$locked->id)->max('version')??0;
   if((int)$data['version']!==(int)$current)
     throw ValidationException::withMessages(['version'=>'This report changed since you opened it. Reload before editing.']);
   $before=[];$after=[];$changes=[];
   foreach(self::FIELDS as $field){
    $old=$locked->getRawOriginal($field);
    $new=$data[$field]??null;
    if(in_array($field,['lga_id','sector_id'],true))$new=(int)$new;
    if($field==='report_category_id'&&$new!==null)$new=(int)$new;
    if($field==='observation_date')$new=date('Y-m-d',strtotime($new));
    if(in_array($field,['latitude','longitude'],true)&&$new!==null)$new=number_format((float)$new,7,'.','');
    if($old!==null&&in_array($field,['latitude','longitude'],true))$old=number_format((float)$old,7,'.','');
    if($old!==null&&$field==='observation_date')$old=substr((string)$old,0,10);
    $before[$field]=$old;$after[$field]=$new;
    if((string)$old!==(string)$new)$changes[$field]=['from'=>$old,'to'=>$new];
   }
   if(!$changes)throw ValidationException::withMessages(['reason'=>'No report field has changed.']);
   $locked->fill($after);
   $locked->save();
   CaseRevision::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,
    'version'=>$current+1,'verification_status_at_edit'=>$locked->verification_status,
    'reason'=>$data['reason'],'before_values'=>$before,'after_values'=>$after,'changes'=>$changes]);
   CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,
    'event_type'=>'report_revised','comment'=>'Revision '.($current+1).': '.$data['reason']]);
   AdminActivityLog::record($r->user(),'case.revised',$locked,
    ['version'=>$current+1,'changed_fields'=>array_keys($changes)]);
  });
 }
}
