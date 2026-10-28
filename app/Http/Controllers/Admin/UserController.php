<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Lga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class UserController extends Controller {
 private function gate(Request $r):void {abort_unless($r->user()?->isAdmin(),403);}
 public function index(Request $r){
  $this->gate($r);
  $q=User::with('assignedLgas')->orderBy('name');
  if($r->filled('q')){$s=addcslashes(trim((string)$r->input('q')),'%_\\');$q->where(fn($u)=>$u->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));}
  return view('admin.users.index',['users'=>$q->paginate(25)->withQueryString(),'lgas'=>Lga::orderBy('name')->get()]);
 }
 public function create(Request $r){$this->gate($r);return view('admin.users.create',['lgas'=>Lga::orderBy('name')->get()]);}
 private function assignmentIds(Request $r,string $role):array {
  $ids=array_map('intval',$r->input('lga_ids',[]));
  if($role!=='focal' && count($ids))throw ValidationException::withMessages(['lga_ids'=>'Only focal persons can be assigned to LGAs.']);
  return $role==='focal'?$ids:[];
 }
 public function store(Request $r){
  $this->gate($r);
  $data=$r->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:255','unique:users,email'],
   'role'=>['required',Rule::in(['admin','meal','focal'])],'password'=>['required','string','min:12','confirmed'],
   'lga_ids'=>['sometimes','array'],'lga_ids.*'=>['integer','distinct','exists:lgas,id']]);
  $ids=$this->assignmentIds($r,$data['role']);unset($data['lga_ids']);
  DB::transaction(function()use($r,$data,$ids){
   $user=User::create([...$data,'is_active'=>true,'must_change_password'=>true]);
   $user->assignedLgas()->sync($ids);
   AdminActivityLog::record($r->user(),'user.created',$user,['role'=>$data['role'],'lga_ids'=>$ids]);
  });
  return redirect()->route('admin.users.index')->with('message','User account created.');
 }
 public function update(Request $r,User $user){
  $this->gate($r);
  $data=$r->validate(['name'=>['required','string','max:150'],'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],
   'role'=>['required',Rule::in(['admin','meal','focal'])],'is_active'=>['required','boolean'],
   'assignments_present'=>['sometimes','boolean'],'lga_ids'=>['sometimes','array'],
   'lga_ids.*'=>['integer','distinct','exists:lgas,id']]);
  if($user->id===$r->user()->id && (!$data['is_active'] || $data['role']!=='admin'))
   return back()->withErrors(['role'=>'You cannot deactivate or demote your own administrator account.']);
  $ids=$this->assignmentIds($r,$data['role']);
  $old=['role'=>$user->role,'is_active'=>$user->is_active,'lga_ids'=>$user->assignedLgas()->pluck('lgas.id')->all()];
  DB::transaction(function()use($r,$user,$data,$ids,$old){
   $user->update(collect($data)->only(['name','email','role','is_active'])->all());
   if($user->role!=='meal' || !$user->is_active){
    \App\Models\CivicCase::where('reviewer_id',$user->id)
     ->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])
     ->update(['reviewer_id'=>null,'review_due_at'=>null]);
   }
   if($r->boolean('assignments_present') || $data['role']!=='focal')$user->assignedLgas()->sync($ids);
   AdminActivityLog::record($r->user(),'user.updated',$user,['before'=>$old,'after'=>[
    'role'=>$user->role,'is_active'=>$user->is_active,'lga_ids'=>$user->assignedLgas()->pluck('lgas.id')->all()]]);
  });
  return redirect()->route('admin.users.index')->with('message','User details updated.');
 }
}
