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
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
