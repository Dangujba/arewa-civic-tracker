package ng.org.rawyod.arewa.security

import android.content.Context
import android.util.Base64
import org.json.JSONObject

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
        .remove("fingerprint_enabled").remove("fingerprint_owner").commit() }
}
