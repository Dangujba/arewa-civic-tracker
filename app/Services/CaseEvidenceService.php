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
            $checksum=hash_file('sha256', $file->getRealPath());
            // Idempotent when a multipart report is retried after the case was saved.
            if (CaseEvidence::where('case_id',$case->id)->where('sha256',$checksum)->exists()) continue;
            $path=$file->store('cases/'.$case->uuid,'local');
            try {
                DB::transaction(function () use ($file,$path,$case,$actor,$checksum) {
                    CaseEvidence::create([
                        'case_id'=>$case->id,'uploaded_by'=>$actor->id,
                        'disk'=>'local','path'=>$path,'original_name'=>basename($file->getClientOriginalName()),
                        'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),
                        'sha256'=>$checksum,
                    ]);
                    CaseEvent::create(['case_id'=>$case->id,'actor_id'=>$actor->id,'event_type'=>'evidence_uploaded']);
                });
            } catch (\Throwable $e) {
                Storage::disk('local')->delete($path);
                throw $e;
            }
        }
    }
}
