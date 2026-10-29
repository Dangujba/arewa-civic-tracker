<?php
namespace Tests\Feature;
use Tests\TestCase;
class RestoreSandboxSafetyTest extends TestCase
{
 public function test_sensitive_restore_requires_explicit_confirmation():void {
  $this->artisan('act:backup-restore-sandbox',[
   'archive'=>'example.tar.gpg','name'=>'recovery-test'
  ])->assertExitCode(1);
 }
 public function test_recovery_name_must_be_safe():void {
  $this->artisan('act:backup-restore-sandbox',[
   'archive'=>'example.tar.gpg','name'=>'../escape','--confirm-isolated'=>true
  ])->assertExitCode(1);
 }
 public function test_missing_key_and_backup_directory_are_rejected():void {
  config()->set('act.backup_dir','/tmp/arewa-no-such-backup-dir');
  config()->set('act.backup_key_file','/tmp/arewa-no-such-backup-key');
  $this->artisan('act:backup-restore-sandbox',[
   'archive'=>'example.tar.gpg','name'=>'recovery-test','--confirm-isolated'=>true
  ])->assertExitCode(1);
 }
}
