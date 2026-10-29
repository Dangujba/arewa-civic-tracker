<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Derivative for authenticated viewing, NEVER a replacement for original evidence.
 * Limited image area prevents decompression bombs; GD strips EXIF/location metadata.
 * JPEG compression is intentionally kept high. Original bytes and SHA-256 are preserved.
 */
final class PrivateEvidencePreview
{
    public function create(UploadedFile $file): ?string
    {
        if (!in_array($file->getMimeType(), ['image/jpeg','image/png','image/webp'],true)) {
            return null;
        }
        $size=@getimagesize($file->getRealPath());
        if (!is_array($size) || !isset($size[0],$size[1])) return null;
        [$width,$height]=$size;
        if ($width<=0 || $height<=0 || $width*$height>24000000) return null;
        $raw=file_get_contents($file->getRealPath());
        if ($raw===false) return null;
        $source=@imagecreatefromstring($raw);
        unset($raw);
        if (!$source) return null;
        try {
            // Keep correct orientation without copying GPS or other EXIF to the preview.
            if ($file->getMimeType()==='image/jpeg' && function_exists('exif_read_data')) {
                $exif=@exif_read_data($file->getRealPath(), 'IFD0', true, false);
                $orientation=(int)($exif['IFD0']['Orientation']??$exif['Orientation']??1);
                if (in_array($orientation,[2,4,5,7],true)) {
                    imageflip($source,in_array($orientation,[2,5,7],true)?IMG_FLIP_HORIZONTAL:IMG_FLIP_VERTICAL);
                }
                $degrees=match($orientation) {3,4=>180,5,6=>-90,7,8=>90,default=>0};
                if ($degrees!==0) {
                    $rotated=imagerotate($source,$degrees,0);
                    if ($rotated!==false) {imagedestroy($source);$source=$rotated;}
                }
            }
            $maxEdge=1800;
            $factor=min(1.0, $maxEdge/max(imagesx($source),imagesy($source)));
            $w=max(1,(int)round(imagesx($source)*$factor));
            $h=max(1,(int)round(imagesy($source)*$factor));
            $scaled=imagescale($source,$w,$h,IMG_BICUBIC_FIXED);
            if (!$scaled) return null;
            try {
                imagealphablending($scaled,true);
                ob_start();
                try {
                    if (function_exists('imagewebp')) $ok=imagewebp($scaled,null,85);
                    else $ok=imagejpeg($scaled,null,88);
                    $bytes=ob_get_contents();
                } finally {
                    ob_end_clean();
                }
                return ($ok && is_string($bytes) && strlen($bytes)>0)?$bytes:null;
            } finally {
                imagedestroy($scaled);
            }
        } finally {
            imagedestroy($source);
        }
    }
}
