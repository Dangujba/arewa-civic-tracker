<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class FieldAccountController extends Controller
{
    private function actor(Request $request): User {
        $user=$request->user();
        abort_unless($user instanceof User && $user->is_active &&
            $user->role==='focal' && !$user->must_change_password &&
            $user->tokenCan('field:report'),403);
        return $user;
    }
    public function profile(Request $request) {
        $u=$this->actor($request);
        return response()->json(['user'=>[
            'id'=>$u->id,'name'=>$u->name,'email'=>$u->email,'role'=>$u->role,
            'two_factor_enabled'=>(bool)$u->two_factor_confirmed_at,
            'two_factor_required_by_org'=>OrganizationSetting::requiresMfa($u->role),
            'has_profile_photo'=>(bool)$u->profile_photo_path,
        ]])->header('Cache-Control','private, no-store');
    }
    public function changePassword(Request $request) {
        $u=$this->actor($request);
        $data=$request->validate([
            'current_password'=>['required','current_password'],
            'password'=>['required','string','min:10','confirmed']
        ]);
        if(Hash::check($data['password'],$u->password)) {
            throw ValidationException::withMessages(['password'=>'Choose a different password.']);
        }
        $u->forceFill(['password'=>$data['password']])->save();
        // Previously issued tokens on other devices cannot continue to operate.
        $current=$u->currentAccessToken()?->id;
        $u->tokens()->when($current,fn($q)=>$q->where('id','!=',$current))->delete();
        AdminActivityLog::record($u,'account.password_updated',$u);
        return response()->json(['message'=>'Password changed successfully.'])
            ->header('Cache-Control','private, no-store');
    }
    public function startTwoFactor(Request $request) {
        $u=$this->actor($request);
        $request->validate(['current_password'=>['required','current_password']]);
        abort_if($u->two_factor_confirmed_at,409,'Two-factor authentication is already enabled.');
        $secret=(new Google2FA())->generateSecretKey(32);
        Cache::put('field-2fa-setup:'.$u->id, $secret, now()->addMinutes(10));
        $issuer='Arewa Civic Tracker';
        $uri='otpauth://totp/'.rawurlencode($issuer.':'.$u->email).
            '?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).
            '&algorithm=SHA1&digits=6&period=30';
        return response()->json(['secret'=>$secret,'uri'=>$uri,'expires_in'=>600])
            ->header('Cache-Control','private, no-store');
    }
    public function confirmTwoFactor(Request $request) {
        $u=$this->actor($request);
        $data=$request->validate(['code'=>['required','digits:6']]);
        abort_if($u->two_factor_confirmed_at,409,'Already enabled.');
        $secret=Cache::get('field-2fa-setup:'.$u->id);
        if(!$secret || !(new Google2FA())->verifyKey($secret,$data['code'],1)) {
            throw ValidationException::withMessages(['code'=>'Invalid or expired authenticator code.']);
        }
        $codes=array_map(fn()=>strtoupper(bin2hex(random_bytes(6))),range(1,8));
        $u->forceFill(['two_factor_secret'=>$secret,'two_factor_confirmed_at'=>now(),
            'two_factor_recovery_codes'=>array_map(fn($v)=>hash('sha256',$v),$codes)])->save();
        Cache::forget('field-2fa-setup:'.$u->id);
        AdminActivityLog::record($u,'account.mfa_enabled',$u);
        return response()->json(['message'=>'Two-factor authentication enabled.',
            'recovery_codes'=>$codes])->header('Cache-Control','private, no-store');
    }
    public function disableTwoFactor(Request $request) {
        $u=$this->actor($request);
        abort_unless($u->two_factor_confirmed_at,409,'Two-factor authentication is not enabled.');
        abort_if(OrganizationSetting::requiresMfa($u->role),403,
            'Your organization requires two-factor authentication.');
        $data=$request->validate(['current_password'=>['required','current_password'],
            'code'=>['required','digits:6']]);
        if(!(new Google2FA())->verifyKey($u->two_factor_secret,$data['code'],1)) {
            throw ValidationException::withMessages(['code'=>'Invalid authenticator code.']);
        }
        $u->forceFill(['two_factor_secret'=>null,'two_factor_confirmed_at'=>null,
            'two_factor_recovery_codes'=>null])->save();
        Cache::forget('field-2fa-setup:'.$u->id);
        AdminActivityLog::record($u,'account.mfa_disabled',$u);
        return response()->json(['message'=>'Two-factor authentication disabled.'])
            ->header('Cache-Control','private, no-store');
    }
}
