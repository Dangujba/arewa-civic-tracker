<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CaseEvidence;
use App\Models\CivicCase;
use App\Models\DataGovernancePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class DataGovernanceController extends Controller
{
 private function guard(Request $r):void {abort_unless($r->user()?->isAdmin(),403);}
 public function index(Request $r){
  $this->guard($r);
  $policy=DataGovernancePolicy::first();
  $preview=['cases'=>null,'evidence'=>null,'audit'=>null];
  if($policy){
   if($policy->case_retention_days)
    $preview['cases']=CivicCase::where('created_at','<',now()->subDays($policy->case_retention_days))
      ->where('legal_hold',false)->count();
   if($policy->evidence_retention_days)
    $preview['evidence']=CaseEvidence::where('created_at','<',now()->subDays($policy->evidence_retention_days))
      ->whereIn('case_id',CivicCase::where('legal_hold',false)->select('id'))->count();
   if($policy->audit_retention_days)
    $preview['audit']=AdminActivityLog::where('created_at','<',now()->subDays($policy->audit_retention_days))->count();
  }
  $base=config('act.backup_dir');
  // open_basedir raises an ErrorException for paths outside the PHP-FPM
  // sandbox. Backup inspection must be non-fatal and must not weaken the sandbox.
  $allowedRoots=array_filter(explode(PATH_SEPARATOR,(string)ini_get('open_basedir')));
  $canInspect=$base && (!$allowedRoots || collect($allowedRoots)->contains(
    fn($root)=>str_starts_with(rtrim($base,'/').'/',rtrim($root,'/').'/')
  ));
  $files=$canInspect && is_dir($base) ? (glob(rtrim($base,'/').'/arewa-*.tar.gpg') ?: []) : [];
  $latest=null;$lastMtime=0;
  foreach($files as $file){
   $mtime=filemtime($file)?:0;
   if($mtime>$lastMtime){$latest=$file;$lastMtime=$mtime;}
  }
  return view('admin.governance.index',[
   'policy'=>$policy,'preview'=>$preview,
   'holds'=>CivicCase::where('legal_hold',true)->count(),
   'backup'=>[
    'accessible'=>(bool)$canInspect,'exists'=>(bool)$latest,'last_at'=>$latest?\Carbon\Carbon::createFromTimestamp($lastMtime):null,
    'age_hours'=>$latest?round((time()-$lastMtime)/3600,1):null,'count'=>count($files)
   ],
   'offsite_configured'=>filled(config('act.offsite_backup_dir')),
  ]);
 }
 public function save(Request $r){
  $this->guard($r);
  $data=$r->validate([
   'case_retention_days'=>['nullable','integer','between:365,36500'],
   'evidence_retention_days'=>['nullable','integer','between:365,36500'],
   'audit_retention_days'=>['nullable','integer','between:365,36500'],
   'notes'=>['nullable','string','max:5000'],
  ]);
  $policy=DB::transaction(function()use($r,$data){
   $policy=DataGovernancePolicy::query()->lockForUpdate()->first();
   if(!$policy){$policy=new DataGovernancePolicy();$policy->id=1;}
   $policy->fill($data);
   $policy->state='draft';$policy->approved_at=null;$policy->approved_by=null;
   $policy->approval_reference=null;$policy->updated_by=$r->user()->id;
   $policy->save();
   AdminActivityLog::record($r->user(),'governance.draft_saved',$policy,
    ['case_days'=>$policy->case_retention_days,'evidence_days'=>$policy->evidence_retention_days,
      'audit_days'=>$policy->audit_retention_days]);
   return $policy;
  });
  return redirect()->route('admin.governance.index')->with('message','Draft policy saved. No data was deleted.');
 }
 public function approve(Request $r){
  $this->guard($r);
  $data=$r->validate([
   'approval_reference'=>['required','string','min:6','max:160'],
   'confirm_no_purge'=>['required','accepted'],
  ]);
  $policy=DB::transaction(function()use($r,$data){
   $policy=DataGovernancePolicy::lockForUpdate()->firstOrFail();
   if(!$policy->case_retention_days && !$policy->evidence_retention_days && !$policy->audit_retention_days)
    throw \Illuminate\Validation\ValidationException::withMessages(['approval_reference'=>'Enter at least one retention period before approval.']);
   $policy->state='approved';$policy->approval_reference=$data['approval_reference'];
   $policy->approved_by=$r->user()->id;$policy->approved_at=now();$policy->save();
   AdminActivityLog::record($r->user(),'governance.policy_approved',$policy,
      ['reference'=>$policy->approval_reference]);
   return $policy;
  });
  return redirect()->route('admin.governance.index')->with('message',
     'Retention policy recorded as approved. Automatic deletion remains disabled.');
 }
 public function legalHold(Request $r,CivicCase $case){
  $this->guard($r);
  $data=$r->validate([
    'hold'=>['required','boolean'],'reason'=>['required','string','min:10','max:3000'],
  ]);
  DB::transaction(function()use($r,$case,$data){
   $locked=CivicCase::whereKey($case->id)->lockForUpdate()->firstOrFail();
   $locked->legal_hold=(bool)$data['hold'];
   $locked->legal_hold_reason=$locked->legal_hold?$data['reason']:null;
   $locked->legal_hold_by=$locked->legal_hold?$r->user()->id:null;
   $locked->save();
   AdminActivityLog::record($r->user(),$locked->legal_hold?'governance.legal_hold_set':'governance.legal_hold_released',
     $locked,['reason'=>$data['reason']]);
  });
  return redirect()->route('cases.show',$case)->with('message',
    (bool)$data['hold']?'Legal hold applied to report.':'Legal hold released.');
 }
}
