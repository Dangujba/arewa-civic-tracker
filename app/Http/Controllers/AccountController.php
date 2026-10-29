<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Hash;
class AccountController extends Controller {
    public function form(Request $request) {
        return response()->view('account.security', ['mustChange'=>(bool) $request->user()->must_change_password,'twoFactorQr'=>app(\App\Http\Controllers\TwoFactorController::class)->qrDataUri($request)])->header('Cache-Control','private, no-store')->header('Pragma','no-cache');
    }
    public function update(Request $request) {
        $data=$request->validate([
            'current_password'=>['required','current_password'],
            'password'=>['required','string','confirmed'],
        ]);
        $user=$request->user();
        if (Hash::check($data['password'],$user->password)) {
            return back()->withErrors(['password'=>'Choose a new password different from the current password.']);
        }
        $user->forceFill(['password'=>$data['password'],'must_change_password'=>false])->save();
        $request->session()->regenerate();
        AdminActivityLog::record($user,'account.password_updated',$user);
        return redirect()->route('dashboard')->with('message','Password updated successfully.');
    }
}
