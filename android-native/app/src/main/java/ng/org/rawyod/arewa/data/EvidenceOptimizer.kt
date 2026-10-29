package ng.org.rawyod.arewa.data

/**
 * Mobile photos are now archived in their original format, not recompressed.
 * The authenticated server creates optimized private previews from originals.
 * This object is retained to document the archive/preview separation.
 */
internal object EvidenceOptimizer {
    const val MAX_ORIGINAL_PHOTO_BYTES: Long = 10L * 1024L * 1024L
    const val MAX_ORIGINAL_PDF_BYTES: Long = 5L * 1024L * 1024L
}
