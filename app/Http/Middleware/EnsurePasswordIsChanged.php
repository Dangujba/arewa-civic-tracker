<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\OrganizationSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsurePasswordIsChanged {
    public function handle(Request $request, Closure $next): Response {
        if ($request->user()?->must_change_password && !$request->routeIs('account.password.form','account.password.update','logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message'=>'Change the temporary password before using the system.','redirect'=>route('account.password.form')],423);
            }
            return redirect()->route('account.password.form');
        }
        $user=$request->user();
        if($user && OrganizationSetting::requiresMfa($user->role) && !$user->two_factor_confirmed_at
           && !$request->routeIs('account.password.form','account.password.update','two-factor.setup','two-factor.confirm','logout')){
            if($request->expectsJson())return response()->json(['message'=>'Activate two-factor authentication before continuing.','redirect'=>route('account.password.form')],423);
            return redirect()->route('account.password.form')->with('message','Your organization requires authenticator-based two-factor authentication.');
        }
        return $next($request);
    }
}
