<?php
namespace App\Services;

use App\Models\CaseEvidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class EvidenceStorageManager
{
    public function r2Configured():bool
    {
        foreach(['key','secret','bucket','endpoint'] as $key) {
            if (!filled(config("filesystems.disks.r2.$key"))) return false;
        }
        return str_starts_with((string)config('filesystems.disks.r2.endpoint'), 'https://');
    }

    public function overview():array
    {
        $rows=CaseEvidence::query()->selectRaw('disk, COUNT(*) as file_count, COALESCE(SUM(size_bytes),0) as bytes')
            ->groupBy('disk')->orderBy('disk')->get();
        return [
            'r2_configured'=>$this->r2Configured(),
            'active_disk'=>config('filesystems.evidence_disk','local'),
            'by_disk'=>$rows,
            'total_files'=>$rows->sum('file_count'),
            'total_bytes'=>$rows->sum('bytes'),
            'r2_ready_for_new_uploads'=>$this->r2Configured() && config('filesystems.evidence_disk')==='r2',
        ];
    }

    /** Stream a stored object; never assume an S3 ETag equals an MD5/SHA digest. */
    public function verify(CaseEvidence $evidence,?string $disk=null,?string $path=null):array
    {
        $disk=$disk??$evidence->disk;
        $path=$path??$evidence->path;
        $filesystem=Storage::disk($disk);
        if (!$filesystem->exists($path)) throw new RuntimeException("Missing file on {$disk}");
        $stream=$filesystem->readStream($path);
        if (!is_resource($stream)) throw new RuntimeException("Unreadable file on {$disk}");
        try{
            $digest=hash_init('sha256');
            $size=hash_update_stream($digest,$stream);
            $hash=hash_final($digest);
        } finally {
            fclose($stream);
        }
        if ($size===false) throw new RuntimeException("Unable to read entire file on {$disk}");
        if (!hash_equals(strtolower($evidence->sha256),strtolower($hash)) ||
            (int)$evidence->size_bytes !== $size) {
            throw new RuntimeException("Checksum or size mismatch on {$disk}");
        }
        return ['bytes'=>$size,'sha256'=>$hash];
    }

    /**
     * Copy and verify before pointing the evidence record to R2.
     * Keep the original local file for independent post-deployment recovery.
     */
    public function migrateToR2(CaseEvidence $evidence):string
    {
        if ($evidence->disk==='r2') {
            $this->verify($evidence,'r2');
            return 'already_remote';
        }
        if ($evidence->disk!=='local') throw new RuntimeException('Unsupported source disk');
        if (!$this->r2Configured()) throw new RuntimeException('R2 credentials or HTTPS endpoint missing');

        $this->verify($evidence,'local');
        $path=$evidence->path;
        $remote=Storage::disk('r2');
        $existed=$remote->exists($path);
        try {
            if (!$existed) {
                $source=Storage::disk('local')->readStream($path);
                if (!is_resource($source)) throw new RuntimeException('Could not read source file');
                try {
                    if (!$remote->writeStream($path,$source,['visibility'=>'private']))
                        throw new RuntimeException('Upload did not finish');
                } finally {
                    fclose($source);
                }
            }
            $this->verify($evidence,'r2',$path);
        } catch (\Throwable $error) {
            // Never remove an object that existed before this attempt.
            if (!$existed) $remote->delete($path);
            throw $error;
        }

        DB::transaction(function()use($evidence){
            $locked=CaseEvidence::query()->whereKey($evidence->id)->lockForUpdate()->firstOrFail();
            if ($locked->disk==='r2') return;
            if ($locked->disk!=='local') throw new RuntimeException('The evidence disk changed during transfer');
            $locked->disk='r2';
            $locked->save();
        });
        return 'migrated';
    }
}
