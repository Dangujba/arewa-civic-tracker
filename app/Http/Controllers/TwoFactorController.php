<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminActivityLog;
use App\Models\OrganizationSetting;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private function totp(): Google2FA {return new Google2FA();}

    public function challengeForm(Request $request)
    {
        if (!$request->session()->has('two_factor_pending_id')) return redirect()->route('login');
        return response()->view('auth.two-factor-challenge')->header('Cache-Control','private, no-store');
    }

    public function challenge(Request $request)
    {
        $code=$request->validate(['code'=>['required','string','max:40']])['code'];
        $id=$request->session()->get('two_factor_pending_id');
        if(!$id)return redirect()->route('login');
        $key='two-factor-login:'.$id.'|'.$request->ip();
        if(RateLimiter::tooManyAttempts($key,5))throw ValidationException::withMessages(['code'=>'Too many attempts. Try again shortly.']);
        $user=User::find($id);
        if(!$user||!$user->is_active||!$user->two_factor_confirmed_at||!$user->two_factor_secret){
            $request->session()->forget('two_factor_pending_id');
            return redirect()->route('login');
        }
        if(!$this->validateCode($user,$code)){
            RateLimiter::hit($key,300);
            throw ValidationException::withMessages(['code'=>'Invalid authentication or recovery code.']);
        }
        RateLimiter::clear($key);
        $request->session()->forget('two_factor_pending_id');
        Auth::login($user);
        $request->session()->regenerate();
        AdminActivityLog::record($user,'account.login_2fa',$user);
        return redirect()->route($user->must_change_password?'account.password.form':'dashboard');
    }

    private function validateCode(User $user,string $code): bool
    {
        $normalized=preg_replace('/[\s-]+/','',strtoupper($code));
        if(preg_match('/^\d{6}$/',$normalized)){
            return $this->totp()->verifyKey($user->two_factor_secret,$normalized,1);
        }
        if(!preg_match('/^[A-F0-9]{12}$/',$normalized))return false;
        $hash=hash('sha256',$normalized);
        return DB::transaction(function()use($user,$hash){
            $locked=User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $saved=$locked->two_factor_recovery_codes??[];
            $index=array_search($hash,$saved,true);
            if($index===false)return false;
            unset($saved[$index]);
            $locked->two_factor_recovery_codes=array_values($saved);
            $locked->save();
            return true;
        });
    }

    public function setup(Request $request)
    {
        $request->validate(['current_password'=>['required','current_password']]);
        abort_if($request->user()->two_factor_confirmed_at,409,'Two-factor authentication is already enabled.');
        $secret=$this->totp()->generateSecretKey(32);
        $request->session()->put('two_factor_setup_secret',$secret);
        $request->session()->put('two_factor_setup_at',time());
        return redirect()->route('account.password.form')->with('message','Scan the QR code and enter a code to activate 2FA.');
    }

    public function confirm(Request $request)
    {
        $data=$request->validate(['code'=>['required','digits:6']]);
        $secret=$request->session()->get('two_factor_setup_secret');
        $created=(int)$request->session()->get('two_factor_setup_at');
        if(!$secret||!$created||time()-$created>600)throw ValidationException::withMessages(['code'=>'The setup expired. Start activation again.']);
        if(!$this->totp()->verifyKey($secret,$data['code'],1))throw ValidationException::withMessages(['code'=>'The authentication code is incorrect.']);
        $user=$request->user();
        abort_if($user->two_factor_confirmed_at,409);
        $codes=array_map(fn()=>strtoupper(bin2hex(random_bytes(6))),range(1,8));
        $user->forceFill([
            'two_factor_secret'=>$secret,'two_factor_confirmed_at'=>now(),
            'two_factor_recovery_codes'=>array_map(fn($code)=>hash('sha256',$code),$codes)
        ])->save();
        AdminActivityLog::record($user,'account.mfa_enabled',$user);
        $request->session()->forget(['two_factor_setup_secret','two_factor_setup_at']);
        return redirect()->route('account.password.form')->with('recovery_codes',$codes)->with('message','Two-factor authentication is now enabled.');
    }

    public function disable(Request $request)
    {
        $data=$request->validate(['current_password'=>['required','current_password'],'code'=>['required','string','max:40']]);
        $user=$request->user();
        abort_unless($user->two_factor_confirmed_at,409,'2FA is not enabled.');
        abort_if(OrganizationSetting::requiresMfa($user->role),403,'Your organization requires two-factor authentication.');
        if(!$this->validateCode($user,$data['code']))throw ValidationException::withMessages(['code'=>'Authentication or recovery code not accepted.']);
        $user->forceFill(['two_factor_secret'=>null,'two_factor_confirmed_at'=>null,'two_factor_recovery_codes'=>null])->save();
        AdminActivityLog::record($user,'account.mfa_disabled',$user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('message','Two-factor authentication disabled. Please sign in again.');
    }

    public function qrDataUri(Request $request): ?string
    {
        $secret=$request->session()->get('two_factor_setup_secret');
        $created=(int)$request->session()->get('two_factor_setup_at');
        if(!$secret||!$created||time()-$created>600)return null;
        $issuer='Arewa Civic Tracker';
        $label=$request->user()->email;
        $uri='otpauth://totp/'.rawurlencode($issuer.':'.$label).'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
        return (new PngWriter())->write(QrCode::create($uri)->setSize(230)->setMargin(10))->getDataUri();
    }
}
