<?php
namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AndroidDownloadController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = config('native.android_apk_path');
        abort_unless(is_string($path) && is_file($path) && is_readable($path), 404);

        $response = response()->download($path, config('native.android_apk_name'), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'X-Content-Type-Options' => 'nosniff',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
        // BinaryFileResponse defaults to public cacheability when prepared.
        // Explicitly mark this private and non-storable for every release.
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

        return $response;
    }
}
