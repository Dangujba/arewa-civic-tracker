<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use Illuminate\Http\Request;
class ExportController extends Controller {
    public function csv(Request $request){
        abort_unless($request->user()->canReview(),403);
        $filters=$request->validate(['lga_id'=>['nullable','integer','exists:lgas,id'],'sector_id'=>['nullable','integer','exists:sectors,id'],'verification_status'=>['nullable','in:submitted,under_review,clarification_requested,verified,approved,rejected'],'type'=>['nullable','in:public_project,governance_issue'],'date_from'=>['nullable','date'],'date_to'=>['nullable','date','after_or_equal:date_from']]);
        $q=CivicCase::visibleTo($request->user())->with(['lga:id,name','sector:id,name'])->orderBy('id');
        foreach(['lga_id','sector_id','verification_status','type'] as $field){if(isset($filters[$field]))$q->where($field,$filters[$field]);}
        if(isset($filters['date_from']))$q->whereDate('observation_date','>=',$filters['date_from']);
        if(isset($filters['date_to']))$q->whereDate('observation_date','<=',$filters['date_to']);
        return response()->streamDownload(function()use($q){
            $out=fopen('php://output','wb');fputcsv($out,['Reference','Type','Title','LGA','Community','Sector','Observed on','Verification','Progress','Description']);
            foreach($q->cursor() as $c){
                $safe=fn($v)=>preg_match('/^[\\s]*[=+@\\-]/u',(string)$v)?"'".(string)$v:(string)$v;
                fputcsv($out,array_map($safe,[$c->reference,$c->type,$c->title,$c->lga?->name,$c->community,$c->sector?->name,$c->observation_date?->format('Y-m-d'),$c->verification_status,$c->progress_status,$c->description]));
            }
            fclose($out);
        },'arewa-civic-reports-'.now()->format('Ymd').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
