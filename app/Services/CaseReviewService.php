<?php
namespace App\Services;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CaseReviewService {
    private const TRANSITIONS=[
        'submitted'=>['under_review','clarification_requested','rejected'],
        'under_review'=>['clarification_requested','verified','rejected'],
        'clarification_requested'=>['under_review','rejected'],
        'verified'=>['approved','clarification_requested','rejected'],
        'approved'=>[], 'rejected'=>[],
    ];
    public function allowedTransitions(User $actor,CivicCase $case):array {
        if (!$actor->canReview() || ($actor->role==='meal' && $case->reviewer_id && (int)$case->reviewer_id !== (int)$actor->id))return [];
        $allowed=self::TRANSITIONS[$case->verification_status]??[];
        return $actor->isAdmin() ? $allowed : array_values(array_diff($allowed,['approved']));
    }
    public function transition(User $actor,CivicCase $case,string $to,?string $comment):CivicCase {
        return DB::transaction(function() use($actor,$case,$to,$comment){
            $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            $from=$locked->verification_status;
            if(!$actor->canReview() || ($actor->role==='meal' && $locked->reviewer_id && (int)$locked->reviewer_id !== (int)$actor->id))abort(403,'Report assigned to another reviewer.');
            if(!in_array($to,self::TRANSITIONS[$from]??[],true)){
                throw ValidationException::withMessages(['status'=>"Transition {$from} to {$to} is not permitted."]);
            }
            if($to==='approved' && !$actor->isAdmin()){abort(403,'Only RAWYOD administrators can approve reports.');}
            if(in_array($to,['rejected','clarification_requested'],true) && !$comment){throw ValidationException::withMessages(['comment'=>'A written reason is required.']);}
            $locked->verification_status=$to;$locked->save();
            CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$actor->id,'event_type'=>'review_status_changed','from_status'=>$from,'to_status'=>$to,'comment'=>$comment]);
            return $locked;
        });
    }
}
