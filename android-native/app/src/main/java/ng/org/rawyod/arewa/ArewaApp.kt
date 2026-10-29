package ng.org.rawyod.arewa

import android.app.Application
import ng.org.rawyod.arewa.data.FieldDatabase
import ng.org.rawyod.arewa.data.FieldRepository
import ng.org.rawyod.arewa.network.FieldApi
import ng.org.rawyod.arewa.security.CryptoVault
import ng.org.rawyod.arewa.security.SessionStore

class ArewaApp : Application() {
    val vault by lazy { CryptoVault() }
    val store by lazy { SessionStore(this, vault) }
    val database by lazy { FieldDatabase.open(this) }
    val api by lazy { FieldApi(vault) }
    val repository by lazy { FieldRepository(this, database.dao(), vault, store, api) }
}
