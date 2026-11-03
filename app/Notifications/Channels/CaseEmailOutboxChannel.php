<?php
namespace App\Notifications\Channels;
use App\Notifications\CaseActionNotification;
use Illuminate\Support\Facades\DB;

class CaseEmailOutboxChannel {
    public function send(object $notifiable, CaseActionNotification $notification): void {
        if (!$notifiable->is_active || !filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) return;
        $data = $notification->toArray($notifiable);
        DB::table('case_email_outbox')->insertOrIgnore([
            'notification_id'=>$notification->id,
            'recipient_id'=>$notifiable->id,
            'case_id'=>$data['case_id'],
            'recipient_email'=>$notifiable->email,
            'subject'=>mb_substr('[Arewa] '.$data['title'].' - '.$data['reference'],0,255),
            'detail'=>$data['detail'],
            'status'=>'pending',
            'attempts'=>0,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
    }
}
