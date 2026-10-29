<?php
namespace App\Console\Commands;
use App\Models\CivicCase;
use App\Notifications\CaseActionNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class NotifyReviewDeadlines extends Command {
 protected $signature='act:review-deadlines';
 protected $description='Create idempotent in-app reminders for due and overdue MEAL reviews.';
 public function handle():int {
  $today=today('Africa/Lagos')->toDateString();
  $count=0;
  CivicCase::query()->whereNotNull('reviewer_id')
   ->whereDate('review_due_at','<=',$today)
   ->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])
   ->where(fn($q)=>$q->whereNull('review_last_notified_on')->orWhereDate('review_last_notified_on','<',$today))
   ->orderBy('id')->chunkById(100,function($cases)use($today,&$count){
    foreach($cases as $case){
     DB::transaction(function()use($case,$today,&$count){
      $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
      if($locked->review_last_notified_on?->toDateString()===$today ||
         !$locked->review_due_at || $locked->review_due_at->toDateString()>$today ||
         !in_array($locked->verification_status,['submitted','under_review','clarification_requested','verified'],true))return;
      $reviewer=$locked->reviewer;
      if(!$reviewer || !$reviewer->is_active || $reviewer->role!=='meal')return;
      $locked->review_last_notified_on=$today;
      $locked->save();
      $reviewer->notify(new CaseActionNotification($locked->id,$locked->reference,
       'Review deadline reminder',
       'Review deadline: '.$locked->review_due_at->format('d M Y').'. Please take action.'));
      $count++;
     });
    }
   });
  $this->info("Review deadline reminders recorded: {$count}");
  return self::SUCCESS;
 }
}
