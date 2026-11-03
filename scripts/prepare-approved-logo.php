<?php
declare(strict_types=1);

/**
 * Derive PNG assets from the original user-approved Arewa logo images.
 * Only resizes official artwork. Never generates replacement branding.
 * Run after git checkout/build to supply email-compatible PNG and favicons.
 */
$root=dirname(__DIR__);
$icons=$root.'/public/icons';
if(!function_exists('imagecreatefromwebp') || !function_exists('imagepng')){
    fwrite(STDERR,"GD with WebP and PNG support is required.\n");
    exit(2);
}
function export(string $source, string $target, int $width, int $height):void{
    $img=@imagecreatefromwebp($source);
    if(!$img)throw new RuntimeException("Could not decode official logo: $source");
    $canvas=imagecreatetruecolor($width,$height);
    $white=imagecolorallocate($canvas,255,255,255);
    imagefilledrectangle($canvas,0,0,$width,$height,$white);
    imagecopyresampled($canvas,$img,0,0,0,0,$width,$height,imagesx($img),imagesy($img));
    if(!imagepng($canvas,$target,7))throw new RuntimeException("Could not write $target");
    imagedestroy($canvas);
    imagedestroy($img);
}
export($icons.'/arewa-approved-logo.webp',$icons.'/arewa-approved-logo.png',450,325);
export($icons.'/arewa-approved-mark.webp',$icons.'/arewa-approved-mark-32.png',32,32);
export($icons.'/arewa-approved-mark.webp',$icons.'/arewa-approved-mark-192.png',192,192);
export($icons.'/arewa-approved-mark.webp',$icons.'/arewa-approved-mark-512.png',512,512);
export($icons.'/arewa-approved-wordmark.webp',$icons.'/arewa-approved-wordmark.png',540,176);
echo "Official logo derivatives generated.\n";
