package ng.org.rawyod.arewa.data

import android.content.ContentResolver
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Matrix
import android.media.ExifInterface
import android.net.Uri
import java.io.ByteArrayOutputStream
import kotlin.math.max

/**
 * Downsamples large JPEG/PNG/WebP photographs before encrypted offline storage.
 * No image metadata or location EXIF is carried into the optimized JPEG.
 * PDF evidence is never re-encoded.
 */
internal object EvidenceOptimizer {
    private const val TARGET_EDGE = 2048
    private const val TARGET_BYTES = 2 * 1024 * 1024
    private const val MAX_BYTES = 5 * 1024 * 1024

    fun optimize(resolver: ContentResolver, uri: Uri, mime: String): ByteArray {
        require(mime.startsWith("image/")) { "Only photographs may be compressed" }
        val options=BitmapFactory.Options().apply { inJustDecodeBounds=true }
        resolver.openInputStream(uri)?.use { BitmapFactory.decodeStream(it,null,options) }
            ?: error("Unable to read photo")
        require(options.outWidth>0 && options.outHeight>0) { "Invalid image file" }
        var sample=1
        while (max(options.outWidth, options.outHeight)/sample > TARGET_EDGE) sample*=2
        val decoded=resolver.openInputStream(uri)?.use {
            BitmapFactory.decodeStream(it,null,BitmapFactory.Options().apply {
                inSampleSize=sample
                inPreferredConfig=Bitmap.Config.ARGB_8888
            })
        } ?: error("Unable to decode photo")
        val orientation=runCatching {
            resolver.openInputStream(uri)?.use { ExifInterface(it).getAttributeInt(
                ExifInterface.TAG_ORIENTATION,ExifInterface.ORIENTATION_NORMAL
            ) } ?: ExifInterface.ORIENTATION_NORMAL
        }.getOrDefault(ExifInterface.ORIENTATION_NORMAL)
        val matrix=Matrix()
        when(orientation) {
            ExifInterface.ORIENTATION_ROTATE_90 -> matrix.postRotate(90f)
            ExifInterface.ORIENTATION_ROTATE_180 -> matrix.postRotate(180f)
            ExifInterface.ORIENTATION_ROTATE_270 -> matrix.postRotate(270f)
            ExifInterface.ORIENTATION_FLIP_HORIZONTAL -> matrix.preScale(-1f,1f)
            ExifInterface.ORIENTATION_FLIP_VERTICAL -> matrix.preScale(1f,-1f)
            ExifInterface.ORIENTATION_TRANSPOSE -> { matrix.preScale(-1f,1f);matrix.postRotate(90f) }
            ExifInterface.ORIENTATION_TRANSVERSE -> { matrix.preScale(-1f,1f);matrix.postRotate(270f) }
        }
        val oriented=if(matrix.isIdentity) decoded else Bitmap.createBitmap(
            decoded,0,0,decoded.width,decoded.height,matrix,true
        ).also { decoded.recycle() }
        try {
            for(quality in listOf(86,75,65,55,42)){
                val out=ByteArrayOutputStream()
                check(oriented.compress(Bitmap.CompressFormat.JPEG,quality,out)) {
                    "Photo compression failed"
                }
                if(out.size()<=TARGET_BYTES || (quality==42 && out.size()<=MAX_BYTES))
                    return out.toByteArray()
            }
            error("Photo could not be reduced below 5 MB")
        } finally { oriented.recycle() }
    }
}
