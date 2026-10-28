<?php
namespace App\Http\Middleware;
use Closure;
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
        return $next($request);
    }
}
