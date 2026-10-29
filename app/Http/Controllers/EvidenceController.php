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
        abort_if($case->closure_status==='closed',403,'Closed cases cannot accept new evidence.');
        if(!($r->user()->can('review',$case) || ($case->reporter_id===$r->user()->id && in_array($case->verification_status,['submitted','clarification_requested'],true)))){abort(403);}
        $r->validate(['evidence'=>['required','file','mimes:jpg,jpeg,png,webp,pdf','max:10240']]);
        app(\App\Services\CaseEvidenceService::class)->attach($case,$r->user(),[$r->file('evidence')]);
        return redirect()->route('cases.show',$case)->with('message','Evidence added.');
    }
    public function preview(Request $r,CaseEvidence $evidence){
        $case=CivicCase::findOrFail($evidence->case_id);
        $r->user()->can('view',$case)||abort(403);
        abort_unless($evidence->preview_path && Storage::disk($evidence->disk)->exists($evidence->preview_path),404);
        return Storage::disk($evidence->disk)->response($evidence->preview_path,null,[
            'Content-Type'=>'image/webp','Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
    public function download(Request $r,CaseEvidence $evidence){
        $case=CivicCase::findOrFail($evidence->case_id);$r->user()->can('view',$case)||abort(403);
        abort_unless(Storage::disk($evidence->disk)->exists($evidence->path),404);
        return Storage::disk($evidence->disk)->download($evidence->path,$evidence->original_name);
    }
}
