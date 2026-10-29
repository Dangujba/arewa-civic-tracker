<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
class CaseActionNotification extends Notification {
 public function __construct(
  private readonly int $caseId,
  private readonly string $reference,
  private readonly string $title,
  private readonly string $detail
 ){}
 public function via(object $notifiable):array{return ['database'];}
 public function toArray(object $notifiable):array{
  return ['case_id'=>$this->caseId,'reference'=>$this->reference,
   'title'=>$this->title,'detail'=>$this->detail,
   'url'=>route('cases.show',$this->caseId)];
 }
}
