<?php
namespace App\Services;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\User;
use App\Support\CaseRichText;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
class CaseSubmissionService {
    public function submit(User $user,array $data):CivicCase {
        $data['description'] = CaseRichText::clean($data['description']);
        ksort($data);$hash=hash('sha256',json_encode($data,JSON_THROW_ON_ERROR));
        $existing=CivicCase::where('reporter_id',$user->id)->where('client_submission_id',$data['client_submission_id'])->first();
        if($existing){return $this->replay($existing,$hash);}
        try{
            return DB::transaction(function()use($user,$data,$hash){
                $case=CivicCase::create([...$data,'uuid'=>(string)Str::uuid(),'reference'=>'ACT-YOB-'.now()->format('Y').'-'.strtoupper(Str::random(10)),'reporter_id'=>$user->id,'payload_hash'=>$hash,'verification_status'=>'submitted']);
                CaseEvent::create(['case_id'=>$case->id,'actor_id'=>$user->id,'event_type'=>'submitted','to_status'=>'submitted']);
                // Notify the review team for new cases only, never an idempotent replay.
                $reviewers=User::query()->where('is_active',true)
                    ->whereIn('role',['admin','meal'])->get();
                foreach($reviewers as $reviewer){
                    $reviewer->notify(new \App\Notifications\CaseActionNotification(
                        $case->id,$case->reference,'New report submitted',
                        'A new monitoring report is awaiting review in your workspace.'
                    ));
                }
                return $case;
            });
        }catch(UniqueConstraintViolationException $e){
            $existing=CivicCase::where('reporter_id',$user->id)->where('client_submission_id',$data['client_submission_id'])->first();
            if($existing){return $this->replay($existing,$hash);}throw $e;
        }
    }
    private function replay(CivicCase $case,string $hash):CivicCase {
        if(!hash_equals($case->payload_hash,$hash)){throw new ConflictHttpException('This offline submission ID was already used with different report data.');}
        return $case;
    }
}
