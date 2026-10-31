<?php
namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AndroidDownloadController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = config('native.android_apk_path');
        abort_unless(is_string($path) && is_file($path) && is_readable($path), 404);

        return response()->download($path, config('native.android_apk_name'), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'X-Content-Type-Options' => 'nosniff',
            // APK changes must not reuse a previously cached download at this URL.
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
