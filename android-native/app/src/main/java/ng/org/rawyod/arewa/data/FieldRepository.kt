package ng.org.rawyod.arewa.data

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import ng.org.rawyod.arewa.network.FieldApi
import ng.org.rawyod.arewa.security.CryptoVault
import ng.org.rawyod.arewa.security.SessionStore
import ng.org.rawyod.arewa.worker.SyncScheduler
import org.json.JSONObject
import java.io.File
import java.util.UUID

data class StagedEvidence(
    val id: String, val path: String, val filename: String,
    val mime: String, val bytes: Long
)
data class RestoredDraft(val id: String,val payload: JSONObject,
                         val files: List<StagedEvidence>,val status: String,val error: String?)

class FieldRepository(
    private val context: Context, val dao: FieldDao, val vault: CryptoVault,
    val store: SessionStore, val api: FieldApi
) {
    val maxEvidenceBytes = 10L * 1024L * 1024L
    private val privateFiles by lazy { File(context.filesDir, "field_evidence").apply { mkdirs() } }

    fun pending(owner: Long): Flow<List<Draft>> = dao.observeDrafts(owner)

    suspend fun prepareEvidence(uri: Uri, filename: String?, mime: String): StagedEvidence =
        withContext(Dispatchers.IO) {
            require(mime in setOf("image/jpeg", "image/png", "image/webp", "application/pdf")) {
                "Only JPEG, PNG, WebP or PDF evidence is accepted."
            }
            val cr = context.contentResolver
            var declared = -1L
            var display = filename ?: "field-evidence"
            if(uri.scheme=="file") {
                val selected=File(uri.path ?: error("Camera file is unavailable"))
                require(selected.isFile){"The camera photograph is missing."}
                declared=selected.length()
                if(filename==null)display=selected.name
            } else cr.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE), null, null, null)?.use {
                if (it.moveToFirst()) {
                    val n = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                    val s = it.getColumnIndex(OpenableColumns.SIZE)
                    if (n >= 0 && !it.isNull(n) && filename == null) display = it.getString(n)
                    if (s >= 0 && !it.isNull(s)) declared = it.getLong(s)
                }
            }
            val isPhoto=mime in setOf("image/jpeg","image/png","image/webp")
            val maxBytes=if(isPhoto)maxEvidenceBytes else 5L*1024L*1024L
            require(declared<0 || declared<=maxBytes) {
                if(isPhoto) "Photograph exceeds 10 MB. Select a smaller image."
                else "PDF exceeds 5 MB"
            }
            // Upload the original bytes exactly, without downscaling, re-encoding or
            // stripping EXIF. The backend creates a sanitized, high-quality preview.
            val input=if(uri.scheme=="file") File(uri.path ?: error("Camera file missing")).inputStream()
                else cr.openInputStream(uri)
            val bytes = input?.use { input ->
                val buffer=java.io.ByteArrayOutputStream()
                val chunk=ByteArray(8192)
                while(buffer.size().toLong()<=maxBytes){
                    val limit=minOf(chunk.size.toLong(),maxBytes+1L-buffer.size()).toInt()
                    if(limit<=0)break
                    val count=input.read(chunk,0,limit)
                    if(count<0)break
                    if(count==0)continue
                    buffer.write(chunk,0,count)
                }
                buffer.toByteArray()
            } ?: error("Cannot open evidence file")
            require(bytes.isNotEmpty() && bytes.size.toLong()<=maxBytes) {
                if(isPhoto) "Photograph exceeds 10 MB" else "PDF exceeds 5 MB"
            }
            val id = UUID.randomUUID().toString()
            val output = File(privateFiles, "$id.aes")
            try {
                bytes.inputStream().use { vault.encryptStream(it, output) }
                StagedEvidence(id, output.absolutePath,
                    display
                        .replace(Regex("[\\r\\n/\\\\]"), "_").take(120),
                    mime, bytes.size.toLong())
            } catch (e: Exception) { output.delete(); throw e }
        }

    suspend fun restoreDraft(id: String): RestoredDraft = withContext(Dispatchers.IO) {
        val owner=store.userId() ?: error("Sign in to restore your report")
        val draft=dao.draft(id,owner) ?: error("Draft not found")
        require(draft.status!="uploading") { "Upload is in progress. Wait for it to finish." }
        // Pause automatic transmission while a user makes corrections.
        val recovered=RestoredDraft(id,decode(draft),dao.evidenceFor(id).map {
            StagedEvidence(it.id,it.encryptedPath,it.name,it.mimeType,it.originalSize)
        },draft.status,draft.lastError)
        dao.mark(id,owner,"editing",null)
        recovered
    }

    suspend fun queue(payload: JSONObject, attachments: List<StagedEvidence>): String =
        withContext(Dispatchers.IO) {
            val owner=store.userId() ?: error("Sign in to save this report")
            require(attachments.size<=5) { "Maximum five supporting documents" }
            require(store.catalog()?.optJSONArray("lgas")?.let { list ->
                (0 until list.length()).any {
                    list.getJSONObject(it).optInt("id")==payload.optInt("lga_id")
                }
            }==true) { "Your LGA has not been assigned" }
            val id=payload.optString("client_submission_id").ifBlank {
                UUID.randomUUID().toString()
            }
            require(dao.ownerOf(id)?.let { it==owner }!=false) {
                "Draft belongs to another account"
            }
            payload.put("client_submission_id",id)
            val old=dao.evidenceFor(id)
            val current=attachments.map { e->
                require(File(e.path).isFile) { "Evidence file is unavailable" }
                Evidence(e.id,id,e.path,e.filename,e.mime,e.bytes)
            }
            dao.replaceDraft(Draft(id,owner,
                vault.encrypt(payload.toString().toByteArray(Charsets.UTF_8)),
                System.currentTimeMillis()),current)
            val currentPaths=current.map { it.encryptedPath }.toSet()
            old.filterNot { it.encryptedPath in currentPaths }
                .forEach { File(it.encryptedPath).delete() }
            SyncScheduler.enqueue(context)
            id
        }

    fun decode(draft: Draft): JSONObject =
        JSONObject(String(vault.decrypt(draft.encryptedPayload), Charsets.UTF_8))

    suspend fun deleteSent(draft: Draft) {
        val files = dao.evidenceFor(draft.id)
        dao.deleteDraft(draft.id, draft.ownerId)
        files.forEach { File(it.encryptedPath).delete() }
    }

    fun abandonUnused(list: List<StagedEvidence>) {
        list.forEach { File(it.path).delete() }
    }
}
