package ng.org.rawyod.arewa.security

import android.content.Context
import android.util.Base64
import org.json.JSONObject
import java.security.MessageDigest
import java.security.SecureRandom
import javax.crypto.SecretKeyFactory
import javax.crypto.spec.PBEKeySpec

class SessionStore(context: Context, private val vault: CryptoVault) {
    private val preferences = context.getSharedPreferences("field_secure_v1", Context.MODE_PRIVATE)
    private fun put(key: String, value: String) {
        val encrypted = vault.encrypt(value.toByteArray(Charsets.UTF_8))
        check(preferences.edit().putString(key, Base64.encodeToString(encrypted, Base64.NO_WRAP)).commit())
    }
    private fun get(key: String): String? {
        val encoded = preferences.getString(key, null) ?: return null
        return String(vault.decrypt(Base64.decode(encoded, Base64.NO_WRAP)), Charsets.UTF_8)
    }
    fun storeSession(token: String, user: JSONObject) {
        put("token", token)
        put("user", user.toString())
    }
    fun token(): String? = get("token")
    fun user(): JSONObject? = get("user")?.let(::JSONObject)
    fun userId(): Long? = user()?.optLong("id")?.takeIf { it > 0 }
    fun cacheAccount(data:JSONObject) = put("account",data.toString())
    fun account():JSONObject? = get("account")?.let(::JSONObject)
    fun cacheReports(data:JSONObject) = put("reports",data.toString())
    fun reports():JSONObject? = get("reports")?.let(::JSONObject)
    fun cacheReport(id:Long, data:JSONObject) = put("report_$id",data.toString())
    fun report(id:Long):JSONObject? = get("report_$id")?.let(::JSONObject)
    fun offlinePinEnabled():Boolean = userId()!=null && get("offline_pin")!=null
    fun setOfflinePin(pin:String) {
        require(userId()!=null && pin.length>=6 && pin.all(Char::isDigit)) { "Use a 6-digit or longer numeric PIN." }
        val salt=ByteArray(16).also(SecureRandom()::nextBytes)
        val key=SecretKeyFactory.getInstance("PBKDF2WithHmacSHA256")
            .generateSecret(PBEKeySpec(pin.toCharArray(),salt,160000,256)).encoded
        put("offline_pin",JSONObject().put("owner",userId()).put("salt",
            Base64.encodeToString(salt,Base64.NO_WRAP)).put("hash",
            Base64.encodeToString(key,Base64.NO_WRAP)).toString())
    }
    fun verifyOfflinePin(pin:String):Boolean {
        val record=runCatching{get("offline_pin")?.let(::JSONObject)}.getOrNull()?:return false
        if(record.optLong("owner",-1)!=userId() || pin.length !in 6..32)return false
        val salt=Base64.decode(record.getString("salt"),Base64.NO_WRAP)
        val check=SecretKeyFactory.getInstance("PBKDF2WithHmacSHA256")
            .generateSecret(PBEKeySpec(pin.toCharArray(),salt,160000,256)).encoded
        return MessageDigest.isEqual(Base64.decode(record.getString("hash"),Base64.NO_WRAP),check)
    }
    fun removeOfflinePin(){preferences.edit().remove("offline_pin").commit()}
    fun cacheCatalog(data: JSONObject) = put("catalog", data.toString())
    fun catalog(): JSONObject? = get("catalog")?.let(::JSONObject)
    /** Leave the owner and encrypted outbox intact when a 30-day token expires. */
    fun invalidateToken() { preferences.edit().remove("token").commit() }
    /** Fingerprint is a per-account, device-local app lock, not a replacement for server login. */
    fun fingerprintEnabled(): Boolean {
        val owner=userId() ?: return false
        return preferences.getLong("fingerprint_owner", -1L)==owner &&
            preferences.getBoolean("fingerprint_enabled",false)
    }
    fun setFingerprintEnabled(value: Boolean) {
        val owner=userId() ?: error("Sign in before enabling fingerprint access.")
        check(preferences.edit().putLong("fingerprint_owner", owner)
            .putBoolean("fingerprint_enabled",value).commit())
    }
    fun clearSession() { preferences.edit().remove("token").remove("user")
        .remove("fingerprint_enabled").remove("fingerprint_owner").remove("offline_pin")
        .remove("catalog").remove("account").remove("reports").commit() }
}
