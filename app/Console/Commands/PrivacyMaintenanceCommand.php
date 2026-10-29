<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PrivacyMaintenanceCommand extends Command
{
 protected $signature='act:privacy-maintenance';
 protected $description='Remove expired authentication artifacts without deleting field reports or evidence.';
 public function handle():int {
  $expired=DB::table('personal_access_tokens')->whereNotNull('expires_at')
   ->where('expires_at','<',now())->delete();
  // An intentionally conservative cleanup window: do not touch active or recent sessions.
  $sessions=DB::table('sessions')->where('last_activity','<',now()->subDays(30)->timestamp)->delete();
  $this->info("Expired device tokens: {$expired}; old web sessions: {$sessions}.");
  return self::SUCCESS;
 }
}
