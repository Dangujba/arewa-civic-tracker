<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class AccountController extends Controller {
    public function form(Request $request) {
        return view('account.security', ['mustChange'=>(bool) $request->user()->must_change_password]);
    }
    public function update(Request $request) {
        $data=$request->validate([
            'current_password'=>['required','current_password'],
            'password'=>['required','confirmed',Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        $user=$request->user();
        if (Hash::check($data['password'],$user->password)) {
            return back()->withErrors(['password'=>'Choose a new password different from the current password.']);
        }
        $user->forceFill(['password'=>$data['password'],'must_change_password'=>false])->save();
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('message','Password updated successfully.');
    }
}
