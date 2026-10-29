<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\ReportCategory;
use App\Models\WorkflowStatusLabel;
use App\Models\AdminActivityLog;
use App\Models\Lga;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferenceDataController extends Controller
{
 private function guard(Request $request):void {abort_unless($request->user()?->isAdmin(),403);}
 public function index(Request $request){
  $this->guard($request);
  return view('admin.reference-data.index',[
   'lgas'=>Lga::orderBy('name')->get(),
   'communities'=>Community::with('lga:id,name')->orderBy('lga_id')->orderBy('name')->paginate(25,['*'],'communities'),
   'categories'=>ReportCategory::orderBy('type')->orderBy('name')->get(),
   'statuses'=>WorkflowStatusLabel::orderBy('kind')->orderBy('code')->get(),
  ]);
 }
 public function addLga(Request $request){
  $this->guard($request);
  $request->merge(['code'=>strtoupper(trim((string)$request->input('code')))]);
  $data=$request->validate([
   'name'=>['required','string','max:100',Rule::unique('lgas','name')],
   'code'=>['required','regex:/^[A-Z0-9]{2,10}$/',Rule::unique('lgas','code')],
  ]);
  $lga=Lga::create($data);
  AdminActivityLog::record($request->user(),'lga.created',$lga,$data);
  return back()->with('message','Local government added.');
 }
 public function updateLga(Request $request,Lga $lga){
  $this->guard($request);
  $request->merge(['code'=>strtoupper(trim((string)$request->input('code')))]);
  $data=$request->validate([
   'name'=>['required','string','max:100',Rule::unique('lgas','name')->ignore($lga->id)],
   'code'=>['required','regex:/^[A-Z0-9]{2,10}$/',Rule::unique('lgas','code')->ignore($lga->id)],
  ]);
  $before=$lga->only(['name','code']);
  $lga->update($data);
  AdminActivityLog::record($request->user(),'lga.updated',$lga,['before'=>$before,'after'=>$data]);
  return back()->with('message','Local government updated. Existing reports and assignments were preserved.');
 }
 public function addCommunity(Request $request){
  $this->guard($request);
  $data=$request->validate([
   'lga_id'=>['required','integer','exists:lgas,id'],
   'name'=>['required','string','max:150',
    Rule::unique('communities','name')->where('lga_id',$request->input('lga_id'))]
  ]);
  $model=Community::create([...$data,'is_active'=>true]);
  AdminActivityLog::record($request->user(),'community.created',$model,['name'=>$model->name]);
  return back()->with('message','Community created.');
 }
 public function updateCommunity(Request $request,Community $community){
  $this->guard($request);
  $data=$request->validate([
   'name'=>['required','string','max:150',Rule::unique('communities','name')
      ->where('lga_id',$community->lga_id)->ignore($community->id)],
   'is_active'=>['required','boolean']
  ]);
  $community->update($data);
  AdminActivityLog::record($request->user(),'community.updated',$community,$data);
  return back()->with('message','Community updated.');
 }
 public function addCategory(Request $request){
  $this->guard($request);
  $data=$request->validate([
   'type'=>['required',Rule::in(['public_project','governance_issue'])],
   'name'=>['required','string','max:120',
      Rule::unique('report_categories','name')->where('type',$request->input('type'))]
  ]);
  $category=ReportCategory::create([...$data,'is_active'=>true]);
  AdminActivityLog::record($request->user(),'category.created',$category,$data);
  return back()->with('message','Report category created.');
 }
 public function updateCategory(Request $request,ReportCategory $category){
  $this->guard($request);
  $data=$request->validate([
   'name'=>['required','string','max:120',
       Rule::unique('report_categories','name')->where('type',$category->type)->ignore($category->id)],
   'is_active'=>['required','boolean']
  ]);
  $category->update($data);
  AdminActivityLog::record($request->user(),'category.updated',$category,$data);
  return back()->with('message','Report category updated.');
 }
 public function updateStatus(Request $request,WorkflowStatusLabel $status){
  $this->guard($request);
  $data=$request->validate(['label'=>['required','string','max:100']]);
  $status->update($data);
  AdminActivityLog::record($request->user(),'status_label.updated',$status,['kind'=>$status->kind,'code'=>$status->code,'label'=>$status->label]);
  return back()->with('message','Status label updated. Workflow transitions remain unchanged.');
 }
}
