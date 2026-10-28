<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
    public function index(Request $request){
        abort_unless((bool)$request->user()->is_active,403);
        $base=CivicCase::visibleTo($request->user());
        $stats=(clone $base)->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN type='public_project' THEN 1 ELSE 0 END) AS projects")
            ->selectRaw("SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) AS approved")
            ->selectRaw("SUM(CASE WHEN verification_status IN ('submitted','under_review','clarification_requested') THEN 1 ELSE 0 END) AS pending")
            ->first();
        $byLga=(clone $base)->select('lga_id',DB::raw('COUNT(*) AS total'))->with('lga:id,name')->groupBy('lga_id')->orderByDesc('total')->limit(17)->get();
        $bySector=(clone $base)->select('sector_id',DB::raw('COUNT(*) AS total'))->with('sector:id,name')->groupBy('sector_id')->orderByDesc('total')->limit(15)->get();
        $recent=(clone $base)->with('lga:id,name')->latest()->limit(8)->get();
        return view('dashboard.index',compact('stats','byLga','bySector','recent'));
    }
}
