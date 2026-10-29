<?php
namespace App\Http\Controllers;
use App\Models\CivicCase;
use App\Services\CaseClosureService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CaseClosureController extends Controller {
 public function update(Request $request,CivicCase $case,CaseClosureService $service){
  abort_unless($request->user()->isAdmin(),403);
  $validated=$request->validate([
   'action'=>['required',Rule::in(['close','reopen'])],
   'reason'=>['required','string','min:10','max:5000'],
  ]);
  $service->change($request->user(),$case,$validated['action'],$validated['reason']);
  return redirect()->route('cases.show',$case)
    ->with('message',$validated['action']==='close'?'Case closed.':'Case reopened.');
 }
}
