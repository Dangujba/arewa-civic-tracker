<?php
namespace App\Services;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaseClosureService {
 public function change(User $actor,CivicCase $case,string $action,string $reason):CivicCase {
  abort_unless($actor->isAdmin(),403);
  return DB::transaction(function()use($actor,$case,$action,$reason){
   $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
   $before=$locked->closure_status;
   if($action==='close'){
    if($before==='closed')throw ValidationException::withMessages(['action'=>'Case is already closed.']);
    if($locked->verification_status!=='approved' ||
       !in_array($locked->progress_status,['completed','resolved'],true))
      throw ValidationException::withMessages(['action'=>'Only approved, completed or resolved reports may be closed.']);
    $locked->closure_status='closed';
    $locked->closed_by=$actor->id;
    $locked->closed_at=now();
    $locked->closure_reason=$reason;
   }elseif($action==='reopen'){
    if($before!=='closed')throw ValidationException::withMessages(['action'=>'Only closed cases may be reopened.']);
    $locked->closure_status='open';
    $locked->closed_by=null;
    $locked->closed_at=null;
    $locked->closure_reason=null;
   }else throw ValidationException::withMessages(['action'=>'Invalid action.']);
   $locked->save();
   CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$actor->id,
    'event_type'=>$action==='close'?'case_closed':'case_reopened',
    'from_status'=>$before,'to_status'=>$locked->closure_status,'comment'=>$reason]);
   AdminActivityLog::record($actor,$action==='close'?'case.closed':'case.reopened',$locked,['reason'=>$reason,'from'=>$before]);
   return $locked;
  });
 }
}
