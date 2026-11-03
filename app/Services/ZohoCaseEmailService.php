<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ZohoCaseEmailService {
    public function configured(): bool {
        $url=(string)config('arewa_notifications.endpoint');
        return filled(config('arewa_notifications.key'))
            && filter_var(config('arewa_notifications.from'),FILTER_VALIDATE_EMAIL)
            && parse_url($url,PHP_URL_SCHEME)==='https'
            && in_array(parse_url($url,PHP_URL_HOST),['cpaas.zoho.eu','cpaas.zoho.com'],true);
    }

    public function send(object $message): bool {
        if(!$this->configured())return false;
        $reportUrl=route('cases.show',$message->case_id);
        $text=$message->subject."\n\n".$message->detail."\n\nOpen the report: ".$reportUrl;
        $html='<!doctype html><html><body style="font-family:Arial,sans-serif;background:#ffffff;color:#171717;max-width:620px;padding:22px;margin:auto">'
            .'<table style="width:100%"><tr><td width="64"><img src="'.e(asset('icons/act-192.png')).'" width="56" height="56" alt="Arewa Civic Tracker"></td>'
            .'<td><strong style="font-size:20px;color:#9E5D37">Arewa Civic Tracker</strong><br><small>RAWYOD | VOICES TO VOTES</small></td></tr></table>'
            .'<hr style="border:0;border-top:3px solid #9E5D37;margin:18px 0">'
            .'<h2 style="font-size:18px">'.e($message->subject).'</h2>'
            .'<p>'.e($message->detail).'</p>'
            .'<p style="padding:14px 0"><a href="'.e($reportUrl).'" style="display:inline-block;background:#9E5D37;color:#fff;padding:13px 20px;text-decoration:none;font-weight:bold">Open Report Securely</a></p>'
            .'<p style="font-size:12px;color:#687b6e">Sign in to Arewa Civic Tracker to view the case. Do not forward this notification to unauthorized recipients.</p>'
            .'</body></html>';
        try {
            $response=Http::timeout(20)->acceptJson()
                ->withHeaders([
                    'Authorization'=>'Zoho-enczapikey '.config('arewa_notifications.key'),
                    'Content-Type'=>'application/json',
                ])
                ->post(config('arewa_notifications.endpoint'),[
                    'from'=>['address'=>config('arewa_notifications.from'),'name'=>'Arewa Civic Tracker'],
                    'to'=>[['email_address'=>['address'=>$message->recipient_email]]],
                    'subject'=>$message->subject,
                    'htmlbody'=>$html,
                    'textbody'=>$text,
                    'client_reference'=>'arewa-'.$message->id,
                    'track_opens'=>false,
                    'track_clicks'=>false,
                ]);
            $json=$response->json();
            $error=!$response->successful() || (is_array($json)
                && (!empty($json['error']) || (is_numeric($json['code']??null) && (int)$json['code']>=400)));
            if($error){
                Log::warning('Case email rejected by provider',['outbox_id'=>$message->id,'status'=>$response->status()]);
                return false;
            }
            return true;
        }catch(\Throwable $error){
            Log::warning('Case email transport failed',['outbox_id'=>$message->id,'exception'=>get_class($error)]);
            return false;
        }
    }
}
