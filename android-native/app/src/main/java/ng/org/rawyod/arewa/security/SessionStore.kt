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
    fun clearSession() { preferences.edit().remove("token").remove("user").commit() }
}
