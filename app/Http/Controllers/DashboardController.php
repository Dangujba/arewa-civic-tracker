<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function index(Request $request){
  $user=$request->user();
  abort_unless((bool)$user->is_active,403);
  if($user->isAdmin())return $this->admin();
  if($user->role==='meal')return $this->meal($user);
  return $this->focal($user);
 }
 private function admin(){
  $base=CivicCase::query();
  $stats=(clone $base)->selectRaw('COUNT(*) AS total')
   ->selectRaw("SUM(CASE WHEN type='public_project' THEN 1 ELSE 0 END) AS projects")
   ->selectRaw("SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) AS approved")
   ->selectRaw("SUM(CASE WHEN verification_status IN ('submitted','under_review','clarification_requested') THEN 1 ELSE 0 END) AS pending")->first();
  $byLga=(clone $base)->select('lga_id',DB::raw('COUNT(*) AS total'))->with('lga:id,name')->groupBy('lga_id')->orderByDesc('total')->limit(17)->get();
  $bySector=(clone $base)->select('sector_id',DB::raw('COUNT(*) AS total'))->with('sector:id,name')->groupBy('sector_id')->orderByDesc('total')->limit(15)->get();
  $recent=(clone $base)->with('lga:id,name')->latest()->limit(8)->get();
  $focalCount=User::where('role','focal')->where('is_active',true)->count();
  $assignedFocals=User::where('role','focal')->where('is_active',true)->whereHas('assignedLgas')->count();
  $lgaCoverage=Lga::whereHas('assignedFocals',fn($q)=>$q->where('role','focal')->where('is_active',true))->count();
  $overdue=(clone $base)->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])
    ->where('review_due_at','<',now())->count();
  return view('dashboard.admin',compact('stats','byLga','bySector','recent','focalCount','assignedFocals','lgaCoverage','overdue'));
 }
 private function meal(User $user){
  $base=CivicCase::query()->where(fn($q)=>$q->whereNull('reviewer_id')->orWhere('reviewer_id',$user->id));
  $stats=(clone $base)->selectRaw('COUNT(*) AS total')
   ->selectRaw("SUM(CASE WHEN verification_status='submitted' THEN 1 ELSE 0 END) AS submitted")
   ->selectRaw("SUM(CASE WHEN verification_status='clarification_requested' THEN 1 ELSE 0 END) AS clarification")
   ->selectRaw("SUM(CASE WHEN verification_status='verified' THEN 1 ELSE 0 END) AS verified")->first();
  $mine=(clone $base)->where('reviewer_id',$user->id)->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])->count();
  $overdue=(clone $base)->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])->where('review_due_at','<',now())->count();
  $recent=(clone $base)->whereIn('verification_status',['submitted','under_review','clarification_requested','verified'])
   ->with(['lga:id,name','reviewer:id,name'])->latest()->limit(12)->get();
  return view('dashboard.meal',compact('stats','mine','overdue','recent'));
 }
 private function focal(User $user){
  $base=CivicCase::query()->where('reporter_id',$user->id);
  $stats=(clone $base)->selectRaw('COUNT(*) AS total')
   ->selectRaw("SUM(CASE WHEN verification_status='approved' THEN 1 ELSE 0 END) AS approved")
   ->selectRaw("SUM(CASE WHEN verification_status='clarification_requested' THEN 1 ELSE 0 END) AS clarification")
   ->selectRaw("SUM(CASE WHEN verification_status IN ('submitted','under_review','verified') THEN 1 ELSE 0 END) AS pending")->first();
  $assignedLgas=$user->assignedLgas()->orderBy('lgas.name')->get();
  $recent=(clone $base)->with('lga:id,name')->latest()->limit(10)->get();
  return view('dashboard.focal',compact('stats','assignedLgas','recent'));
 }
}
