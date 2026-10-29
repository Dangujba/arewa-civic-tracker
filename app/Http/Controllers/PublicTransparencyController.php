<?php
namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\WorkflowStatusLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTransparencyController extends Controller
{
    /**
     * The public query is purpose-built; it must never reuse an authenticated
     * case query or return private evidence, narratives, GPS or user identities.
     */
    private function published(Request $request)
    {
        $f = $request->validate([
            'lga_id' => ['nullable','integer','exists:lgas,id'],
            'sector_id' => ['nullable','integer','exists:sectors,id'],
            'type' => ['nullable','in:public_project,governance_issue'],
            'progress_status' => ['nullable','in:observed,not_started,ongoing,stalled,completed,resolved,other'],
            'q' => ['nullable','string','max:100'],
        ]);
        $query = CivicCase::query()
            ->where('verification_status','approved')
            ->whereNotNull('published_at');
        foreach(['lga_id','sector_id','type','progress_status'] as $key) {
            if(isset($f[$key])) $query->where($key,$f[$key]);
        }
        if(!empty($f['q'])) {
            $q=addcslashes(trim($f['q']), '%_\\');
            $query->where(function($builder)use($q) {
                $builder->where('title','like',"%{$q}%")
                    ->orWhere('community','like',"%{$q}%")
                    ->orWhere('reference','like',"%{$q}%");
            });
        }
        return $query;
    }

    public function index(Request $request)
    {
        $query=$this->published($request);
        $totals=(clone $query)->selectRaw(
            "COUNT(*) AS total, SUM(CASE WHEN type='public_project' THEN 1 ELSE 0 END) AS projects, ".
            "SUM(CASE WHEN type='governance_issue' THEN 1 ELSE 0 END) AS issues, COUNT(DISTINCT lga_id) AS lgas"
        )->first();
        $byLga=(clone $query)->select('lga_id',DB::raw('COUNT(*) AS total'))
            ->with('lga:id,name')->groupBy('lga_id')->orderByDesc('total')->limit(17)->get();
        $reports=(clone $query)->with('lga:id,name','sector:id,name')
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString();
        return response()->view('public.transparency',[
            'totals'=>$totals,'byLga'=>$byLga,'reports'=>$reports,
            'lgas'=>Lga::orderBy('name')->get(['id','name']),
            'sectors'=>Sector::where('is_active',true)->orderBy('name')->get(['id','name']),
            'progressLabels'=>WorkflowStatusLabel::labels('progress'),
        ])->header('Cache-Control','no-store');
    }

    public function mapData(Request $request)
    {
        $query=$this->published($request)->whereNotNull('latitude')->whereNotNull('longitude');
        $total=(clone $query)->count();
        $labels=WorkflowStatusLabel::labels('progress');
        $features=(clone $query)->with('lga:id,name','sector:id,name')
            ->orderByDesc('published_at')->orderByDesc('id')->limit(300)->get()
            ->map(static function(CivicCase $case)use($labels) {
                // Obscure GPS to ~1km grid resolution before leaving the server.
                $lat=round((float)$case->latitude,2);
                $lng=round((float)$case->longitude,2);
                return [
                    'type'=>'Feature',
                    'geometry'=>['type'=>'Point','coordinates'=>[$lng,$lat]],
                    'properties'=>[
                        'title'=>$case->title,
                        'reference'=>$case->reference,
                        'type'=>$case->type,
                        'community'=>$case->community,
                        'lga'=>$case->lga?->name,
                        'sector'=>$case->sector?->name,
                        'progress'=>$labels[$case->progress_status]??$case->progress_status,
                    ],
                ];
            })->values();
        return response()->json([
            'type'=>'FeatureCollection',
            'features'=>$features,
            'meta'=>['total'=>$total,'returned'=>$features->count(),'approximate_coordinates'=>true,'limit'=>300]
        ])->header('Cache-Control','no-store');
    }
}
