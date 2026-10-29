<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ProgressController extends Controller {
    public function update(Request $r,CivicCase $case){
        abort_unless($r->user()->can('review',$case),403);
        $data=$r->validate(['progress_status'=>['required',Rule::in(['observed','not_started','ongoing','stalled','completed','resolved','other'])],'note'=>['required','string','min:5','max:5000']]);
        DB::transaction(function()use($case,$r,$data){
            $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->user()->can('review',$locked),403);
            $previous=$locked->progress_status;
            $locked->progress_status=$data['progress_status'];$locked->save();
            CaseEvent::create(['case_id'=>$locked->id,'actor_id'=>$r->user()->id,'event_type'=>'progress_status_updated','from_status'=>$previous,'to_status'=>$data['progress_status'],'comment'=>$data['note']]);
        });
        return redirect()->route('cases.show',$case)->with('message','Progress updated.');
    }
}
