<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CopyOffsiteBackupCommand extends Command
{
 protected $signature='act:copy-backup-offsite {--archive= : Private encrypted backup filename; defaults to most recent}';
 protected $description='Copy a verified encrypted snapshot onto an explicitly mounted independent destination.';

 public function handle():int
 {
  $source=config('act.backup_dir');
  $destination=config('act.offsite_backup_dir');
  if(!is_string($source)||!is_string($destination)||$destination===''){
   $this->error('An off-server backup destination has not been configured.');
   return self::FAILURE;
  }
  $source=realpath($source);
  $destination=realpath($destination);
  if(!$source||!$destination||!is_dir($destination)||is_link($destination)||
      !is_writable($destination) || str_starts_with($destination,$source.'/')){
   $this->error('Backup or external destination unavailable or unsafe.');
   return self::FAILURE;
  }
  $mount=new Process(['mountpoint','-q',$destination]);
  $mount->run();
  if(!$mount->isSuccessful() || (stat($source)['dev']??null)===(stat($destination)['dev']??null)){
   $this->error('Destination must be a separate verified mounted filesystem.');
   return self::FAILURE;
  }
  $selection=(string)($this->option('archive')?:'');
  if($selection){
   if(basename($selection)!==$selection||!preg_match('/^arewa-[a-zA-Z0-9_-]+\.tar\.gpg$/D',$selection)){
    $this->error('Archive filename invalid.');
    return self::FAILURE;
   }
   $file=$source.'/'.$selection;
  }else{
   $files=glob($source.'/arewa-*.tar.gpg')?:[];
   usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));
   $file=$files[0]??'';
  }
  if(!is_file($file)||is_link($file)||!is_readable($file)){
   $this->error('Encrypted source snapshot not found.');
   return self::FAILURE;
  }
  $name=basename($file);
  $final=$destination.'/'.$name;
  $temp=$destination.'/.'.$name.'.partial-'.bin2hex(random_bytes(5));
  if(file_exists($final)){
   if(is_file($final)&&hash_equals(hash_file('sha256',$file),hash_file('sha256',$final))){
    $this->info('Encrypted offsite copy already verified: '.$name);
    return self::SUCCESS;
   }
   $this->error('Destination filename exists with a different checksum; refusing overwrite.');
   return self::FAILURE;
  }
  $from=null;$to=null;
  try{
   $from=fopen($file,'rb');
   $to=fopen($temp,'xb');
   if(!$from||!$to)throw new \RuntimeException('Could not open stream.');
   chmod($temp,0600);
   $written=stream_copy_to_stream($from,$to);
   if($written===false || $written!==filesize($file))throw new \RuntimeException('Incomplete transfer.');
   fclose($from);$from=null;fclose($to);$to=null;
   if(!hash_equals(hash_file('sha256',$file),hash_file('sha256',$temp)))
    throw new \RuntimeException('Encrypted backup checksum mismatch.');
   if(!rename($temp,$final))throw new \RuntimeException('Cannot commit verified backup.');
   chmod($final,0600);
   $this->info('Verified encrypted backup copied to configured independent mount: '.$name);
   return self::SUCCESS;
  }catch(\Throwable $error){
   report($error);
   $this->error('Independent backup copy failed; source snapshot remains intact.');
   return self::FAILURE;
  }finally{
   if(is_resource($from))fclose($from);
   if(is_resource($to))fclose($to);
   if(file_exists($temp))@unlink($temp);
  }
 }
}
