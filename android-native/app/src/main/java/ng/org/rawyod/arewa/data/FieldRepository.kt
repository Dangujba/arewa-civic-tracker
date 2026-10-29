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

class FieldRepository(
    private val context: Context, val dao: FieldDao, val vault: CryptoVault,
    val store: SessionStore, val api: FieldApi
) {
    val maxEvidenceBytes = 5L * 1024L * 1024L
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
            cr.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME, OpenableColumns.SIZE), null, null, null)?.use {
                if (it.moveToFirst()) {
                    val n = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                    val s = it.getColumnIndex(OpenableColumns.SIZE)
                    if (n >= 0 && !it.isNull(n) && filename == null) display = it.getString(n)
                    if (s >= 0 && !it.isNull(s)) declared = it.getLong(s)
                }
            }
            require(declared <= maxEvidenceBytes) { "Evidence file exceeds 5 MB" }
            // A bounded read also checks providers that do not disclose file size.
            val bytes = cr.openInputStream(uri)?.use { input ->
                val buffer = java.io.ByteArrayOutputStream()
                val chunk = ByteArray(8192)
                while (buffer.size() <= maxEvidenceBytes) {
                    val take = input.read(chunk, 0,
                        minOf(chunk.size.toLong(), maxEvidenceBytes + 1 - buffer.size()).toInt())
                    if (take <= 0) break
                    buffer.write(chunk, 0, take)
                }
                buffer.toByteArray()
            } ?: error("Cannot open evidence file")
            require(bytes.size <= maxEvidenceBytes) { "Evidence file exceeds 5 MB" }
            val id = UUID.randomUUID().toString()
            val output = File(privateFiles, "$id.aes")
            try {
                bytes.inputStream().use { vault.encryptStream(it, output) }
                StagedEvidence(id, output.absolutePath,
                    display.replace(Regex("[\\r\\n/\\\\]"), "_").take(120),
                    mime, bytes.size.toLong())
            } catch (e: Exception) { output.delete(); throw e }
        }

    suspend fun queue(payload: JSONObject, attachments: List<StagedEvidence>): String =
        withContext(Dispatchers.IO) {
            val owner = store.userId() ?: error("Sign in to save this report")
            require(attachments.size <= 5) { "Maximum five supporting documents" }
            require(store.catalog()?.optJSONArray("lgas")?.let { list ->
                (0 until list.length()).any { list.getJSONObject(it).optInt("id") == payload.optInt("lga_id") }
            } == true) { "Your LGA has not been assigned" }
            val id = payload.optString("client_submission_id").ifBlank { UUID.randomUUID().toString() }
            payload.put("client_submission_id", id)
            dao.putDraft(Draft(id, owner,
                vault.encrypt(payload.toString().toByteArray(Charsets.UTF_8)), System.currentTimeMillis()))
            for (file in attachments) dao.putEvidence(Evidence(
                file.id, id, file.path, file.filename, file.mime, file.bytes))
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
