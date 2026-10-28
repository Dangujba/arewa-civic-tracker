<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller {
    public function form(){return view('auth.login');}
    public function login(Request $r){
        $data=$r->validate(['email'=>['required','email'],'password'=>['required','string']]);
        $key=strtolower($data['email']).'|'.$r->ip();
        if(RateLimiter::tooManyAttempts($key,5)){throw ValidationException::withMessages(['email'=>'Too many attempts. Please try again later.']);}
        if(!Auth::attempt([...$data,'is_active'=>true])){RateLimiter::hit($key,60);throw ValidationException::withMessages(['email'=>'Invalid credentials.']);}
        RateLimiter::clear($key);$r->session()->regenerate();return redirect()->intended('/cases');
    }
    public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/login');}
}
