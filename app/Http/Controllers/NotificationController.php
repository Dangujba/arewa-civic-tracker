<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class NotificationController extends Controller {
 public function index(Request $r){
  abort_unless((bool)$r->user()->is_active,403);
  return view('notifications.index',[
   'notifications'=>$r->user()->notifications()->paginate(20),
   'unreadCount'=>$r->user()->unreadNotifications()->count()]);
 }
 public function read(Request $r,string $id){
  abort_unless((bool)$r->user()->is_active,403);
  $note=$r->user()->notifications()->whereKey($id)->firstOrFail();
  $note->markAsRead();
  $case=(int)($note->data['case_id']??0);
  if($case){
   $report=\App\Models\CivicCase::find($case);
   if($report && $r->user()->can('view',$report))return redirect()->route('cases.show',$report);
  }
  return redirect()->route('notifications.index');
 }
}
