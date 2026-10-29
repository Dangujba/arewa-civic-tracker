<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RestoreBackupSandboxCommand extends Command
{
 protected $signature='act:backup-restore-sandbox
    {archive : Filename of an encrypted backup in the configured backup directory}
    {name : Unique, short name for an isolated recovery folder}
    {--confirm-isolated : Confirm that the decrypted files will contain sensitive data}';
 protected $description='Verify and unpack a secure snapshot into a private sandbox, never into the live system.';

 public function handle():int
 {
  if(!$this->option('confirm-isolated')){
   $this->error('Pass --confirm-isolated to acknowledge sensitive decrypted data.');
   return self::FAILURE;
  }
  $name=(string)$this->argument('name');
  if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,47}$/D',$name)){
   $this->error('Use a 3 to 48 character safe snapshot name.');
   return self::FAILURE;
  }
  $base=config('act.backup_dir');
  $key=config('act.backup_key_file');
  $base=$base ? realpath($base) : false;
  $key=$key ? realpath($key) : false;
  if(!$base||!$key||!is_readable($key)){
   $this->error('Configured backup directory or decryption key unavailable.');
   return self::FAILURE;
  }
  $archive=realpath($base.DIRECTORY_SEPARATOR.basename((string)$this->argument('archive')));
  if(!$archive || !str_starts_with($archive,$base.DIRECTORY_SEPARATOR)
      || !str_ends_with($archive,'.tar.gpg')){
   $this->error('Backup must be a *.tar.gpg snapshot in the configured private backup directory.');
   return self::FAILURE;
  }
  $recoveryBase=config('act.recovery_dir');
  if(!$recoveryBase){$this->error('Set ACT_RECOVERY_DIR outside the application before restoring.');return self::FAILURE;}
  if(!is_dir($recoveryBase) && !mkdir($recoveryBase,0700,true) && !is_dir($recoveryBase)){
   $this->error('Cannot create the private recovery directory.');
   return self::FAILURE;
  }
  $recoveryBase=realpath($recoveryBase);
  $live=realpath(base_path());
  if(!$recoveryBase||$recoveryBase==='/'||
    ($live && ($recoveryBase===$live || str_starts_with($live,$recoveryBase.'/') || str_starts_with($recoveryBase,$live.'/')))){
   $this->error('Unsafe recovery directory.');
   return self::FAILURE;
  }
  $target=$recoveryBase.DIRECTORY_SEPARATOR.$name;
  if(file_exists($target)){
   $this->error('Recovery folder already exists. Never overwrite an existing restore.');
   return self::FAILURE;
  }
  chmod($recoveryBase,0700);
  mkdir($target,0700);
  chmod($target,0700);
  try{
   $bundle=$target.'/backup.tar';
   $this->runCheckedProcess(['gpg','--batch','--yes','--pinentry-mode','loopback',
     '--passphrase-file',$key,'-o',$bundle,'--decrypt',$archive]);
   $this->checkPaths($bundle,['database.sql','private.tar','manifest.json']);
   $this->runCheckedProcess(['tar','--no-same-owner','--no-same-permissions','-xf',$bundle,'-C',$target]);
   $manifest=json_decode(file_get_contents($target.'/manifest.json')?:'',true,512,JSON_THROW_ON_ERROR);
   foreach(['database'=>'database.sql','private'=>'private.tar'] as $keyName=>$file){
    $actual=hash_file('sha256',$target.'/'.$file);
    if(!is_string($actual)||!hash_equals((string)($manifest[$keyName.'_sha256']??''),$actual))
      throw new \RuntimeException('Snapshot hash mismatch.');
   }
   $this->checkPaths($target.'/private.tar');
   mkdir($target.'/evidence',0700);
   $this->runCheckedProcess(['tar','--no-same-owner','--no-same-permissions','-xf',
     $target.'/private.tar','-C',$target.'/evidence']);
   unlink($bundle);
   unlink($target.'/private.tar');
   $walk=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
     $target,\FilesystemIterator::SKIP_DOTS));
   foreach($walk as $file){
    if($file->isFile())chmod($file->getPathname(),0600);
   }
   file_put_contents($target.'/RECOVERY_NOTES.txt',
      "Snapshot extracted to an isolated private directory.\n".
      "Do not import database.sql into the active application.\n".
      "Restore requires an approved separate recovery host and validation.\n");
   chmod($target.'/RECOVERY_NOTES.txt',0600);
   $this->info('Verified isolated recovery prepared: '.$name);
   return self::SUCCESS;
  }catch(\Throwable $error){
   report($error);
   $this->purge($target);
   $this->error('Recovery failed; partial sensitive files were deleted. Check application logs.');
   return self::FAILURE;
  }
 }

 private function checkPaths(string $archive,?array $only=null):void
 {
  $result=$this->runCheckedProcess(['tar','-tf',$archive],true);
  foreach(array_filter(explode("\n",trim($result->getOutput()))) as $path){
   if(str_starts_with($path,'/')||preg_match('~(^|/)\.\.(/|$)~',$path))
    throw new \RuntimeException('Unsafe archive path.');
   if($only!==null && !in_array($path,$only,true))
    throw new \RuntimeException('Unexpected archive entry.');
  }
 }
 private function runCheckedProcess(array $args,bool $capture=false):Process
 {
  $process=new Process($args);
  $process->setTimeout(300);
  if(!$capture)$process->disableOutput();
  $process->run();
  if(!$process->isSuccessful())throw new \RuntimeException('Recovery subprocess failed: '.$args[0]);
  return $process;
 }
 private function purge(string $target):void
 {
  if(!is_dir($target))return;
  foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
    $target,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST) as $item){
   if($item->isDir()&&!$item->isLink())@rmdir($item->getPathname());
   else @unlink($item->getPathname());
  }
  @rmdir($target);
 }
}
