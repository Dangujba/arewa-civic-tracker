<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use Illuminate\Http\Request;
class MapController extends Controller {
    public function page(Request $r){abort_unless($r->user()->is_active,403);return view('map.index',['lgas'=>Lga::orderBy('name')->get(),'sectors'=>Sector::orderBy('name')->get()]);}
    public function data(Request $r){
        abort_unless($r->user()->is_active,403);
        $data=$r->validate(['lga_id'=>['nullable','integer','exists:lgas,id'],'sector_id'=>['nullable','integer','exists:sectors,id'],'verification_status'=>['nullable','in:submitted,under_review,clarification_requested,verified,approved,rejected']]);
        $query=CivicCase::visibleTo($r->user())->whereNotNull('latitude')->whereNotNull('longitude');
        foreach(['lga_id','sector_id','verification_status'] as $k){if(isset($data[$k])){$query->where($k,$data[$k]);}}
        $rows=$query->with(['lga:id,name','sector:id,name'])->limit(1000)->get();
        return response()->json(['type'=>'FeatureCollection','features'=>$rows->map(fn($c)=>['type'=>'Feature','geometry'=>['type'=>'Point','coordinates'=>[(float)$c->longitude,(float)$c->latitude]],'properties'=>['id'=>$c->id,'reference'=>$c->reference,'title'=>$c->title,'type'=>$c->type,'verification_status'=>$c->verification_status,'lga'=>$c->lga->name,'sector'=>$c->sector->name,'url'=>route('cases.show',$c)]])->values()]);
    }
}
