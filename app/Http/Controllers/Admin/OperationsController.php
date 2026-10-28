<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Http\Request;
class OperationsController extends Controller {
 private function guard(Request $r):void {abort_unless($r->user()?->isAdmin(),403);}
 public function index(Request $r){
  $this->guard($r);
  $focals=User::where('role','focal')->with('assignedLgas')
   ->withCount(['reports','reports as approved_reports_count'=>fn($q)=>$q->where('verification_status','approved'),
    'reports as clarification_reports_count'=>fn($q)=>$q->where('verification_status','clarification_requested')])
   ->orderBy('name')->get();
  $reviewers=User::where('role','meal')->withCount([
   'assignedReviews',
   'assignedReviews as pending_reviews_count'=>fn($q)=>$q->whereIn('verification_status',['submitted','under_review','clarification_requested','verified']),
   'assignedReviews as overdue_reviews_count'=>fn($q)=>$q->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])->where('review_due_at','<',now()),
  ])->orderBy('name')->get();
  $lgas=Lga::withCount(['assignedFocals as focal_count'=>fn($q)=>$q->where('role','focal')->where('is_active',true)])->orderBy('name')->get();
  $overdue=CivicCase::with(['lga:id,name','reviewer:id,name'])->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])
   ->whereNotNull('review_due_at')->where('review_due_at','<',now())->orderBy('review_due_at')->limit(30)->get();
  $settings=OrganizationSetting::first();
  return view('admin.operations.index',compact('focals','reviewers','lgas','overdue','settings'));
 }
 public function audit(Request $r){
  $this->guard($r);
  $logs=AdminActivityLog::with('actor:id,name')->latest('id')->paginate(35);
  return view('admin.operations.audit',compact('logs'));
 }
 public function settings(Request $r){
  $this->guard($r);
  $data=$r->validate(['review_sla_days'=>['required','integer','between:1,30'],
   'require_mfa_admin'=>['required','boolean'],'require_mfa_meal'=>['required','boolean']]);
  if($data['require_mfa_admin'] && !User::where('role','admin')->where('is_active',true)->whereNotNull('two_factor_confirmed_at')->exists())
   return back()->withErrors(['require_mfa_admin'=>'Enroll at least one active administrator in 2FA before enforcing it.']);
  OrganizationSetting::query()->updateOrCreate(['id'=>1],$data);
  AdminActivityLog::record($r->user(),'organization.review_sla_updated',null,$data);
  return redirect()->route('admin.operations.index')->with('message','Review deadline setting saved for new assignments.');
 }
}
