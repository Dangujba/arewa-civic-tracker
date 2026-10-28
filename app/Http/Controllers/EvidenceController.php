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
        app(\App\Services\CaseEvidenceService::class)->attach($case,$r->user(),[$r->file('evidence')]);
        return redirect()->route('cases.show',$case)->with('message','Evidence added.');
    }
    public function download(Request $r,CaseEvidence $evidence){
        $case=CivicCase::findOrFail($evidence->case_id);$r->user()->can('view',$case)||abort(403);
        abort_unless(Storage::disk($evidence->disk)->exists($evidence->path),404);
        return Storage::disk($evidence->disk)->download($evidence->path,$evidence->original_name);
    }
}
