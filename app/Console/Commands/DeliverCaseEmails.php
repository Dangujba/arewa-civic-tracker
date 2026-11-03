<?php
namespace App\Console\Commands;
use App\Services\ZohoCaseEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeliverCaseEmails extends Command {
    protected $signature='act:send-notification-emails {--limit=40}';
    protected $description='Send and retry private case alert messages from the Zoho CPaaS outbox.';

    public function handle(ZohoCaseEmailService $mailer): int {
        if(!$mailer->configured()){
            $this->warn('Email provider not configured. Outbox retained.');
            return self::SUCCESS;
        }
        $limit=max(1,min(100,(int)$this->option('limit')));
        $sent=0;$fail=0;
        for($i=0;$i<$limit;$i++){
            $row=DB::transaction(function(){
                $entry=DB::table('case_email_outbox')
                    ->where(function($q){
                        $q->whereIn('status',['pending','retry'])
                          ->orWhere(function($q){
                              $q->where('status','processing')
                                ->where('updated_at','<',now()->subMinutes(10));
                          });
                    })
                    ->where(function($q){
                        $q->whereNull('next_attempt_at')
                          ->orWhere('next_attempt_at','<=',now());
                    })
                    ->where('attempts','<',6)->orderBy('id')
                    ->lockForUpdate()->first();
                if(!$entry)return null;
                DB::table('case_email_outbox')->where('id',$entry->id)->update([
                    'status'=>'processing','attempts'=>$entry->attempts+1,
                    'updated_at'=>now()
                ]);
                return $entry;
            });
            if(!$row)break;
            if($mailer->send($row)){
                DB::table('case_email_outbox')->where('id',$row->id)->update([
                    'status'=>'sent','sent_at'=>now(),'error_code'=>null,'updated_at'=>now()
                ]);
                $sent++;
            }else{
                $attempt=(int)$row->attempts+1;
                DB::table('case_email_outbox')->where('id',$row->id)->update([
                    'status'=>$attempt>=6?'failed':'retry',
                    'next_attempt_at'=>$attempt>=6?null:now()->addMinutes(min(120,2**min($attempt,6))),
                    'error_code'=>'delivery-failed','updated_at'=>now()
                ]);
                $fail++;
            }
        }
        $this->info("Email alerts sent: {$sent}; pending retry/failed: {$fail}.");
        return self::SUCCESS;
    }
}
