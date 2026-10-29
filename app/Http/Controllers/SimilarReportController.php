<?php
namespace App\Http\Controllers;

use App\Models\CivicCase;
use App\Services\SimilarReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SimilarReportController extends Controller
{
    public function check(Request $request,SimilarReportService $service){
        abort_unless($request->user()->can('create',CivicCase::class),403);
        $data=$request->validate([
            'title'=>['required','string','min:5','max:200'],
            'lga_id'=>['required','integer','exists:lgas,id'],
            'community'=>['nullable','string','max:150'],
            'sector_id'=>['nullable','integer','exists:sectors,id'],
            'type'=>['nullable',Rule::in(['public_project','governance_issue'])],
            'observation_date'=>['nullable','date','before_or_equal:today']
        ]);
        if($request->user()->role==='focal' &&
            !$request->user()->assignedLgas()->where('lgas.id',$data['lga_id'])->exists())abort(403);
        return response()->json(['matches'=>$service->find($request->user(),$data),
            'message'=>'Suggestions do not prevent a valid separate report.'])
            ->header('Cache-Control','private, no-store');
    }
}
