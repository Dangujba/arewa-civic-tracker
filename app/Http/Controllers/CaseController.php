<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreCaseRequest;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\CaseEvent;
use App\Models\CaseFollowup;
use App\Services\CaseSubmissionService;
use App\Services\CaseReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CaseController extends Controller {
    public function index(Request $r){
        $this->authorizeCase('viewAny');
        $query=CivicCase::visibleTo($r->user())->with(['lga','sector'])->latest();
        if($r->filled('lga_id')){$query->where('lga_id',$r->integer('lga_id'));}
        if($r->filled('verification_status')){$query->where('verification_status',$r->string('verification_status'));}
        return view('cases.index',['cases'=>$query->paginate(20)->withQueryString(),'lgas'=>Lga::orderBy('name')->get()]);
    }
    public function queue(Request $r){
        abort_unless($r->user()->canReview(),403);
        $statuses=['submitted','under_review','clarification_requested','verified'];
        $data=$r->validate(['status'=>['nullable','in:submitted,under_review,clarification_requested,verified']]);
        $counts=array_fill_keys($statuses,0);
        foreach (CivicCase::query()->whereIn('verification_status',$statuses)->selectRaw('verification_status,COUNT(*) AS aggregate')->groupBy('verification_status')->get() as $row) {
            $counts[$row->verification_status]=(int)$row->aggregate;
        }
        $query=CivicCase::query()->whereIn('verification_status',$statuses)->with(['lga:id,name','sector:id,name'])->latest();
        if(!empty($data['status']))$query->where('verification_status',$data['status']);
        return view('cases.queue',['counts'=>$counts,'cases'=>$query->paginate(20)]);
    }
    public function create(Request $r){$r->user()->can('create',CivicCase::class)||abort(403);return view('cases.create',['lgas'=>Lga::orderBy('name')->get(),'sectors'=>Sector::where('is_active',true)->orderBy('name')->get()]);}
    public function store(StoreCaseRequest $r,CaseSubmissionService $service){
        $case=$service->submit($r->user(),$r->validated());
        if($r->expectsJson()){return response()->json(['id'=>$case->id,'reference'=>$case->reference,'verification_status'=>$case->verification_status,'message'=>'Report submitted successfully.','redirect'=>route('cases.show',$case)],201);}
        return redirect()->route('cases.show',$case)->with('message','Report submitted: '.$case->reference);
    }
    public function show(Request $r,CivicCase $case){$r->user()->can('view',$case)||abort(403);return view('cases.show',['case'=>$case->load(['lga','sector','events.actor','followups','evidence']),'allowedTransitions'=>app(CaseReviewService::class)->allowedTransitions($r->user(),$case)]);}
    public function review(Request $r,CivicCase $case,CaseReviewService $service){
        $r->user()->can('review',$case)||abort(403);
        $data=$r->validate(['status'=>['required','in:under_review,clarification_requested,verified,approved,rejected'],'comment'=>['nullable','string','max:5000']]);
        $updated=$service->transition($r->user(),$case,$data['status'],$data['comment']??null);
        return $r->expectsJson()?response()->json(['status'=>$updated->verification_status,'message'=>'Review decision recorded.','reload'=>true]):redirect()->route('cases.show',$case)->with('message','Review recorded.');
    }
    public function followup(Request $r,CivicCase $case){
        $r->user()->can('addFollowup',$case)||abort(403);
        $data=$r->validate(['note'=>['required','string','min:5','max:5000']]);
        DB::transaction(function()use($case,$r,$data){
            CaseFollowup::create(['case_id'=>$case->id,'author_id'=>$r->user()->id,'note'=>$data['note']]);
            CaseEvent::create(['case_id'=>$case->id,'actor_id'=>$r->user()->id,'event_type'=>'followup_added','comment'=>$data['note']]);
        });
        return redirect()->route('cases.show',$case)->with('message','Follow-up added.');
    }
    private function authorizeCase(string $action):void{auth()->user()->can($action,CivicCase::class)||abort(403);}
}
