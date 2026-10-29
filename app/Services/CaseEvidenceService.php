<?php
namespace App\Services;

use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\CaseEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CaseEvidenceService
{
    /** @param UploadedFile[] $files */
    public function attach(CivicCase $case, User $actor, array $files): void
    {
        foreach ($files as $file) {
            // Hash ORIGINAL upload. Preserve every original byte for audit / review.
            $checksum=hash_file('sha256', $file->getRealPath());
            if (CaseEvidence::where('case_id',$case->id)->where('sha256',$checksum)->exists()) continue;
            $disk=config('filesystems.evidence_disk','local');
            $path=$file->store('cases/'.$case->uuid,$disk);
            $previewPath=null;
            try {
                // A failed preview must never destroy valid original evidence.
                $preview=app(PrivateEvidencePreview::class)->create($file);
                if ($preview!==null) {
                    $candidate='cases/'.$case->uuid.'/previews/'.\Illuminate\Support\Str::uuid().'.webp';
                    if (Storage::disk($disk)->put($candidate,$preview,['visibility'=>'private'])) {
                        $previewPath=$candidate;
                    }
                }
                DB::transaction(function () use ($file,$path,$disk,$case,$actor,$checksum,$previewPath,$preview) {
                    CaseEvidence::create([
                        'case_id'=>$case->id,'uploaded_by'=>$actor->id,
                        'disk'=>$disk,'path'=>$path,'original_name'=>basename($file->getClientOriginalName()),
                        'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),
                        'sha256'=>$checksum,
                        'preview_path'=>$previewPath,
                        'preview_size_bytes'=>$previewPath!==null?strlen($preview):null,
                        'preview_sha256'=>$previewPath!==null?hash('sha256',$preview):null,
                    ]);
                    CaseEvent::create(['case_id'=>$case->id,'actor_id'=>$actor->id,'event_type'=>'evidence_uploaded']);
                });
            } catch (\Throwable $e) {
                Storage::disk($disk)->delete($path);
                if($previewPath!==null) Storage::disk($disk)->delete($previewPath);
                throw $e;
            }
        }
    }
}
