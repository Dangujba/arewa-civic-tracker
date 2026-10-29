package ng.org.rawyod.arewa.network

import ng.org.rawyod.arewa.BuildConfig
import ng.org.rawyod.arewa.data.Evidence
import ng.org.rawyod.arewa.security.CryptoVault
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.ByteArrayOutputStream
import java.io.File
import java.io.IOException
import java.util.concurrent.TimeUnit

class ApiFailure(val code: Int, message: String) : IOException(message)

class FieldApi(private val vault: CryptoVault) {
    private val client = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(60, TimeUnit.SECONDS)
        .writeTimeout(60, TimeUnit.SECONDS)
        .callTimeout(120, TimeUnit.SECONDS)
        .followRedirects(false)
        .build()

    private fun call(path: String, method: String, token: String? = null,
                     body: okhttp3.RequestBody? = null): JSONObject {
        val builder = Request.Builder().url(BuildConfig.API_ROOT + path)
            .header("Accept", "application/json")
            .header("Cache-Control", "no-store")
        if(token != null) builder.header("Authorization","Bearer $token")
        when(method) {
            "POST" -> builder.post(body ?: "{}".toRequestBody("application/json".toMediaType()))
            "PUT" -> builder.put(body ?: "{}".toRequestBody("application/json".toMediaType()))
            else -> builder.get()
        }
        client.newCall(builder.build()).execute().use { response ->
            val raw = response.body?.string().orEmpty()
            val payload = runCatching { JSONObject(raw) }.getOrDefault(JSONObject())
            if(!response.isSuccessful) throw ApiFailure(response.code,
                payload.optString("message", "Request failed (HTTP ${response.code})"))
            return payload
        }
    }

    suspend fun profile(token:String):JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {call("account/profile","GET",token)}
    suspend fun changePassword(token:String,current:String,newPassword:String):JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("account/password","POST",token,
                JSONObject().put("current_password",current).put("password",newPassword)
                    .put("password_confirmation",newPassword).toString().toRequestBody("application/json".toMediaType()))
        }
    suspend fun startTwoFactor(token:String,password:String):JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("account/2fa/setup","POST",token,
                JSONObject().put("current_password",password).toString().toRequestBody("application/json".toMediaType()))
        }
    suspend fun confirmTwoFactor(token:String,code:String):JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("account/2fa/confirm","POST",token,
                JSONObject().put("code",code).toString().toRequestBody("application/json".toMediaType()))
        }
    suspend fun disableTwoFactor(token:String,password:String,code:String):JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("account/2fa/disable","POST",token,
                JSONObject().put("current_password",password).put("code",code)
                    .toString().toRequestBody("application/json".toMediaType()))
        }

    suspend fun login(email: String, password: String): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("login", "POST",body=JSONObject().put("email",email)
                .put("password",password).put("device_name","Arewa Native Android")
                .toString().toRequestBody("application/json".toMediaType()))
        }

    suspend fun verifyOtp(challenge: String, otp: String): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("login/verify-otp", "POST",body=JSONObject()
                .put("challenge",challenge).put("otp",otp)
                .toString().toRequestBody("application/json".toMediaType()))
        }

    suspend fun logout(token: String): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("logout", "POST", token)
        }

    suspend fun bootstrap(token: String): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("bootstrap","GET",token)
        }

    suspend fun reports(token: String): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("reports","GET",token)
        }

    suspend fun report(token: String, id: Long): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("reports/$id","GET",token)
        }

    suspend fun reviseReport(token: String, reportId: Long, request: JSONObject): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("reports/"+reportId, "PUT", token,
                request.toString().toRequestBody("application/json".toMediaType()))
        }

    suspend fun similarReports(token: String, request: JSONObject): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("reports/similar","POST",token,
                request.toString().toRequestBody("application/json".toMediaType()))
        }

    suspend fun clarification(token: String, id: Long, response: String) =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            call("reports/$id/clarification","POST",token,
                JSONObject().put("response",response).toString()
                    .toRequestBody("application/json".toMediaType()))
        }

    suspend fun upload(token: String, payload: JSONObject, attachments: List<Evidence>): JSONObject =
        kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            val multipart = MultipartBody.Builder().setType(MultipartBody.FORM)
            val names = listOf("client_submission_id","type","title","description","lga_id",
                "community","sector_id","report_category_id","observation_date","observations","duty_bearers",
                "progress_status","latitude","longitude")
            for (field in names) {
                if(field=="report_category_id" && payload.optInt(field,0)<=0) continue
                val text = payload.optString(field)
                if(text.isNotBlank()) multipart.addFormDataPart(field,text)
            }
            for (e in attachments) {
                val file = File(e.encryptedPath)
                val out = ByteArrayOutputStream()
                vault.decryptTo(file,out)
                val bytes=out.toByteArray()
                require(bytes.size <= (if(e.mimeType=="application/pdf") 5 else 10) * 1024 * 1024) {
                    "Evidence file exceeds upload limit"
                }
                multipart.addFormDataPart(
                    "evidence[]",e.name,
                    bytes.toRequestBody(e.mimeType.toMediaTypeOrNull()
                        ?: "application/octet-stream".toMediaType())
                )
            }
            call("reports","POST",token,multipart.build())
        }
}
