<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    public function profile(Request $request) {
        return response()->view('account.profile', [
            'user'=>$request->user(),
        ])->header('Cache-Control','private, no-store');
    }

    public function photo(Request $request) {
        $path=$request->user()->profile_photo_path;
        if (!$path || !Storage::disk('local')->exists($path)) abort(404);
        $extension=strtolower(pathinfo($path,PATHINFO_EXTENSION));
        $contentType=match($extension) {
            'png'=>'image/png',
            'webp'=>'image/webp',
            default=>'image/jpeg',
        };
        $response=response()->file(Storage::disk('local')->path($path),[
            'Content-Type'=>$contentType,
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
        // Symfony's BinaryFileResponse defaults to public caching. Override it last.
        $response->headers->set('Cache-Control','private, no-store');
        return $response;
    }

    public function uploadPhoto(Request $request) {
        $data=$request->validate([
            'photo'=>['required','image','mimes:jpeg,png,webp','max:2048'],
        ]);
        $user=$request->user();
        $file=$data['photo'];
        $type=$file->getMimeType();
        $extension=match($type){
            'image/jpeg'=>'jpg',
            'image/png'=>'png',
            'image/webp'=>'webp',
            default=>null,
        };
        if (!$extension) {
            return back()->withErrors(['photo'=>'Choose a JPEG, PNG or WebP image.']);
        }
        $newPath=Storage::disk('local')->putFileAs(
            'profile-photos',$file,Str::uuid().'.'.$extension
        );
        $previous=$user->profile_photo_path;
        try {
            $user->forceFill(['profile_photo_path'=>$newPath])->save();
            AdminActivityLog::record($user,'account.profile_photo_updated',$user);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }
        if ($previous) Storage::disk('local')->delete($previous);
        return redirect()->route('account.profile')->with('message','Profile photo updated.');
    }

    public function removePhoto(Request $request) {
        $user=$request->user();
        $previous=$user->profile_photo_path;
        if ($previous) {
            $user->forceFill(['profile_photo_path'=>null])->save();
            AdminActivityLog::record($user,'account.profile_photo_removed',$user);
            Storage::disk('local')->delete($previous);
        }
        return redirect()->route('account.profile')->with('message','Profile photo removed.');
    }

}
