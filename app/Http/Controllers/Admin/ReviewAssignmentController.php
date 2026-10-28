<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CaseEvent;
use App\Models\CivicCase;
use App\Models\OrganizationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ReviewAssignmentController extends Controller {
 public function update(Request $r,CivicCase $case){
  abort_unless($r->user()->isAdmin(),403);
  $data=$r->validate(['reviewer_id'=>['nullable','integer',Rule::exists('users','id')->where('role','meal')->where('is_active',true)],
   'review_due_at'=>['nullable','date','after:today']]);
  DB::transaction(function() use($r,$case,$data){
   $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
   $previous=$locked->reviewer_id;
   $locked->reviewer_id=$data['reviewer_id']??null;
   $locked->review_due_at=($data['reviewer_id']??null)
    ?($data['review_due_at']??now()->addDays(OrganizationSetting::reviewDays())):null;
   $locked->save();
   CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,
    'event_type'=>'reviewer_assigned','comment'=>'Reviewer assignment updated.']);
   AdminActivityLog::record($r->user(),'review.assignment',$locked,[
    'previous_reviewer_id'=>$previous,'reviewer_id'=>$locked->reviewer_id,
    'review_due_at'=>$locked->review_due_at?->toDateTimeString()]);
  });
  return redirect()->route('cases.show',$case)->with('message','Reviewer assignment updated.');
 }
}
