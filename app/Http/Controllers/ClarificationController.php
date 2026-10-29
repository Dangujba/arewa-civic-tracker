<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\CaseFollowup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ClarificationController extends Controller {
    public function respond(Request $r,CivicCase $case){
        abort_unless($r->user()->is_active && $r->user()->id===$case->reporter_id,403);
        $data=$r->validate(['response'=>['required','string','min:5','max:5000']]);
        DB::transaction(function()use($r,$case,$data){
            $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->closure_status==='closed',403,'Closed cases cannot be updated.');
            if($locked->verification_status!=='clarification_requested')throw ValidationException::withMessages(['response'=>'There is no active clarification request for this report.']);
            CaseFollowup::create(['case_id'=>$locked->id,'author_id'=>$r->user()->id,'note'=>$data['response']]);
            $locked->verification_status='under_review';$locked->save();
            CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,'event_type'=>'clarification_response','from_status'=>'clarification_requested','to_status'=>'under_review','comment'=>$data['response']]);
            if($locked->reviewer?->is_active)
                $locked->reviewer->notify(new \App\Notifications\CaseActionNotification(
                    $locked->id,$locked->reference,'Field clarification received',
                    'The focal person has provided a clarification for your review.'));
        });
        return redirect()->route('cases.show',$case)->with('message','Clarification submitted for review.');
    }
}
