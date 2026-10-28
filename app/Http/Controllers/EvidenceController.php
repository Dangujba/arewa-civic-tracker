<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\CaseEvidence;
use App\Models\CaseEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class EvidenceController extends Controller {
    public function store(Request $r,CivicCase $case){
        $r->user()->can('view',$case)||abort(403);
        if(!($r->user()->canReview() || ($case->reporter_id===$r->user()->id && in_array($case->verification_status,['submitted','clarification_requested'],true)))){abort(403);}
        $r->validate(['evidence'=>['required','file','mimes:jpg,jpeg,png,webp,pdf','max:5120']]);
        $file=$r->file('evidence');$path=$file->store('cases/'.$case->uuid,'local');
        try{DB::transaction(function()use($file,$path,$r,$case){
            CaseEvidence::create(['case_id'=>$case->id,'uploaded_by'=>$r->user()->id,'disk'=>'local','path'=>$path,'original_name'=>basename($file->getClientOriginalName()),'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),'sha256'=>hash_file('sha256',$file->getRealPath())]);
            CaseEvent::create(['case_id'=>$case->id,'actor_id'=>$r->user()->id,'event_type'=>'evidence_uploaded']);
        });}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
        return redirect()->route('cases.show',$case)->with('message','Evidence added.');
    }
    public function download(Request $r,CaseEvidence $evidence){
        $case=CivicCase::findOrFail($evidence->case_id);$r->user()->can('view',$case)||abort(403);
        abort_unless(Storage::disk($evidence->disk)->exists($evidence->path),404);
        return Storage::disk($evidence->disk)->download($evidence->path,$evidence->original_name);
    }
}
