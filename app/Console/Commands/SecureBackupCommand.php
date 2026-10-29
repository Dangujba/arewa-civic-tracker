<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class SecureBackupCommand extends Command
{
 protected $signature='act:secure-backup {--verify= : Validate a prior encrypted backup without restoring it}';
 protected $description='Encrypt a MySQL and private-evidence backup or verify a saved snapshot.';

 public function handle():int
 {
  $key=config('act.backup_key_file');
  $dir=config('act.backup_dir') ?: storage_path('app/private/secure-backups');
  if(!$key || !is_file($key) || !is_readable($key)){
   $this->error('Backup encryption key unavailable. Configure ACT_BACKUP_KEY_FILE with an owner-readable file.');
   return self::FAILURE;
  }
  $this->ensurePrivateDirectory($dir);
  if($this->option('verify')){
   $file=(string)$this->option('verify');
   if(!is_file($file) || !str_starts_with(realpath($file)?:'',realpath($dir).DIRECTORY_SEPARATOR)){
    $this->error('Verify only encrypted backups in the configured private directory.');
    return self::FAILURE;
   }
   return $this->verify($file,$key)?self::SUCCESS:self::FAILURE;
  }
  if(config('database.default')!=='mysql'){
   $this->error('Backups currently require the configured MySQL database.');
   return self::FAILURE;
  }
  $temp=sys_get_temp_dir().'/arewa-backup-'.Str::random(20);
  $this->ensurePrivateDirectory($temp);
  $credentials=$temp.'/client.cnf';
  $dump=$temp.'/database.sql';
  $privateTar=$temp.'/private.tar';
  $payload=$temp.'/backup.tar';
  $final=$dir.'/arewa-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.tar.gpg';
  try{
   $db=config('database.connections.mysql');
   $lines=['[client]'];
   foreach(['username'=>'user','password'=>'password','host'=>'host','port'=>'port'] as $keyName=>$configName){
    $value=(string)($db[$keyName]??'');
    $lines[]=$configName.'="'.addcslashes($value,"\\\"").'"';
   }
   file_put_contents($credentials,implode("\n",$lines)."\n",LOCK_EX);
   chmod($credentials,0600);
   $this->runProcess(['mysqldump','--defaults-extra-file='.$credentials,
      '--single-transaction','--no-tablespaces','--quick',
      '--result-file='.$dump,(string)$db['database']]);
   if(!is_file($dump) || filesize($dump)<25)throw new \RuntimeException('Database export is empty.');
   $source=storage_path('app/private');
   $this->runProcess(['tar','--exclude=./secure-backups','-cf',$privateTar,'-C',$source,'.']);
   $manifest=['created_at'=>now()->toIso8601String(),
     'database_sha256'=>hash_file('sha256',$dump),
     'private_sha256'=>hash_file('sha256',$privateTar)];
   file_put_contents($temp.'/manifest.json',json_encode($manifest,JSON_THROW_ON_ERROR));
   $this->runProcess(['tar','-cf',$payload,'-C',$temp,'database.sql','private.tar','manifest.json']);
   $this->runProcess(['gpg','--batch','--yes','--pinentry-mode','loopback','--passphrase-file',config('act.backup_key_file'),
       '--symmetric','--cipher-algo','AES256','--compress-algo','ZLIB','-o',$final,$payload]);
   chmod($final,0600);
   if(!$this->verify($final,config('act.backup_key_file')))
      throw new \RuntimeException('Encrypted backup failed independent verification.');
   $this->info('Verified encrypted backup: '.basename($final));
   return self::SUCCESS;
  }catch(\Throwable $error){
   @unlink($final);
   report($error);
   $this->error('Backup failed. See application logs; no field records were changed.');
   return self::FAILURE;
  }finally{$this->removeDirectory($temp);}
 }

 private function verify(string $file,string $key):bool {
  $temp=sys_get_temp_dir().'/arewa-verify-'.Str::random(16);
  $this->ensurePrivateDirectory($temp);
  try{
   $archive=$temp.'/backup.tar';
   $this->runProcess(['gpg','--batch','--yes','--pinentry-mode','loopback',
    '--passphrase-file',$key,'-o',$archive,'--decrypt',$file]);
   $this->runProcess(['tar','-xf',$archive,'-C',$temp]);
   $manifest=json_decode(file_get_contents($temp.'/manifest.json')?:'',true,512,JSON_THROW_ON_ERROR);
   foreach(['database'=>'database.sql','private'=>'private.tar'] as $name=>$path){
    if(!hash_equals($manifest[$name.'_sha256']??'',hash_file('sha256',$temp.'/'.$path)?:''))
     throw new \RuntimeException($name.' archive fingerprint mismatch.');
   }
   $this->runProcess(['tar','-tf',$temp.'/private.tar']);
   $this->info('Encrypted backup integrity check passed: '.basename($file));
   return true;
  }catch(\Throwable $error){
   report($error);
   $this->error('Encrypted backup verification failed.');
   return false;
  }finally{$this->removeDirectory($temp);}
 }
 private function runProcess(array $command):void{
  $process=new Process($command);
  $process->setTimeout(300);
  $process->disableOutput();
  $process->run();
  if(!$process->isSuccessful())throw new \RuntimeException('Backup subprocess failed: '.$command[0]);
 }
 private function ensurePrivateDirectory(string $path):void{
  if(!is_dir($path) && !mkdir($path,0700,true) && !is_dir($path))
   throw new \RuntimeException('Cannot create private directory.');
  chmod($path,0700);
 }
 private function removeDirectory(string $directory):void {
  if(!is_dir($directory))return;
  foreach(new \RecursiveIteratorIterator(
   new \RecursiveDirectoryIterator($directory,\FilesystemIterator::SKIP_DOTS),
   \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
    if($item->isDir())@rmdir($item->getPathname());else @unlink($item->getPathname());
  }
  @rmdir($directory);
 }
}
