<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CaseEvent;
use App\Models\CivicCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicPublicationController extends Controller
{
    public function update(Request $request, CivicCase $case)
    {
        abort_unless($request->user()?->isAdmin(),403);
        $data=$request->validate([
            'action'=>['required','in:publish,unpublish'],
            'confirm_public'=>['required_if:action,publish','accepted'],
        ]);
        $message=DB::transaction(function()use($request,$case,$data) {
            $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
            if($data['action']==='publish') {
                if($locked->verification_status!=='approved') {
                    throw ValidationException::withMessages(['action'=>'Only approved reports may be published.']);
                }
                if($locked->published_at!==null) return 'This report is already public.';
                $locked->published_at=now();
                $locked->published_by=$request->user()->id;
                $message='This approved report is now visible in the public transparency view.';
            } else {
                if($locked->published_at===null) return 'This report is already private.';
                $locked->published_at=null;
                $locked->published_by=null;
                $message='Report removed from the public transparency view.';
            }
            $locked->save();
            CaseEvent::create([
                'case_id'=>$locked->id,'actor_id'=>$request->user()->id,
                'event_type'=>$data['action']==='publish'?'public_published':'public_unpublished',
                'comment'=>'Public display metadata only. No evidence or internal notes.',
            ]);
            AdminActivityLog::record($request->user(),'case.publication',$locked,
                ['published'=>$data['action']==='publish']);
            return $message;
        });
        return redirect()->route('cases.show',$case)->with('message',$message);
    }
}
