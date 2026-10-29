<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NativeReleaseController extends Controller
{
    public function download(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(),403);
        $path=storage_path('app/private/native-release/arewa-civic-tracker-v0.3.0-debug.apk');
        abort_unless(is_file($path),404);

        $response=response()->download($path,'arewa-civic-tracker-v0.3.0-debug.apk',[
            'Content-Type'=>'application/vnd.android.package-archive',
            'X-Content-Type-Options'=>'nosniff',
        ]);
        $response->headers->set('Cache-Control','private, no-store');
        return $response;
    }
}
