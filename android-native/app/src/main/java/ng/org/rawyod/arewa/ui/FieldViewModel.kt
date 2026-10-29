package ng.org.rawyod.arewa.ui

import android.app.Application
import android.net.Uri
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import java.net.UnknownHostException
import java.net.SocketTimeoutException
import ng.org.rawyod.arewa.network.ApiFailure
import ng.org.rawyod.arewa.ArewaApp
import ng.org.rawyod.arewa.data.Draft
import ng.org.rawyod.arewa.data.StagedEvidence
import ng.org.rawyod.arewa.data.RestoredDraft
import ng.org.rawyod.arewa.worker.SyncScheduler
import org.json.JSONObject
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import java.util.UUID

data class Choice(val id: Int, val name: String)
data class CategoryChoice(val id:Int,val name:String,val type:String)
data class CommunityChoice(val id:Int,val name:String,val lgaId:Int)
data class ReportRow(val id: Long, val reference: String, val title: String, val status: String)
data class ReportDetails(val id: Long, val reference: String, val title: String,
                         val status: String, val description: String, val followups: List<String>,
                         val fields: JSONObject,val version:Int,val editable:Boolean)

class FieldViewModel(application: Application) : AndroidViewModel(application) {
    private val app = application as ArewaApp
    private val repo = app.repository
    var userName by mutableStateOf(app.store.user()?.optString("name").orEmpty())
        private set
    var userId by mutableStateOf(app.store.userId())
        private set
    var message by mutableStateOf("")
    var refreshingReports by mutableStateOf(false)
        private set
    var refreshingCatalog by mutableStateOf(false)
        private set
    var account by mutableStateOf(app.store.account() ?: app.store.user() ?: JSONObject())
        private set
    var twoFactorSecret by mutableStateOf("")
        private set
    var recoveryCodes by mutableStateOf(emptyList<String>())
        private set
    var offlinePinEnabled by mutableStateOf(app.store.offlinePinEnabled())
        private set
    private var offlineUnlocked=false
    private var pinFailures=0
        private set
    var busy by mutableStateOf(false)
        private set
    var loginChallenge by mutableStateOf<String?>(null)
        private set
    var lgas by mutableStateOf(emptyList<Choice>())
        private set
    var sectors by mutableStateOf(emptyList<Choice>())
        private set
    var categories by mutableStateOf(emptyList<CategoryChoice>())
        private set
    var communities by mutableStateOf(emptyList<CommunityChoice>())
        private set
    var similarMatches by mutableStateOf(emptyList<String>())
        private set
    var drafts by mutableStateOf(emptyList<Draft>())
        private set
    var reports by mutableStateOf(readReports(app.store.reports()))
        private set
        private set
    var selectedReport by mutableStateOf<ReportDetails?>(null)
        private set
    var attachments by mutableStateOf(emptyList<StagedEvidence>())
        private set
    var restoredDraft by mutableStateOf<RestoredDraft?>(null)
        private set
    var requiresAuthentication by mutableStateOf(userId!=null && app.store.token()==null)
        private set
    var fingerprintEnabled by mutableStateOf(app.store.fingerprintEnabled())
        private set
    var isBiometricLocked by mutableStateOf(
        (fingerprintEnabled || offlinePinEnabled) && userId!=null && app.store.token()!=null
    )
        private set

    /** Sensitive screens must stay hidden after the activity leaves the foreground. */
    fun lockOnBackground() {
        if((fingerprintEnabled || offlinePinEnabled) && userId!=null && !requiresAuthentication) isBiometricLocked=true
    }
    fun unlockWithFingerprint() {
        if(fingerprintEnabled && userId!=null) {
            offlineUnlocked=true
            requiresAuthentication=false
            isBiometricLocked=false
        }
    }
    fun changeFingerprintSetting(enabled:Boolean) {
        if(userId==null || requiresAuthentication) return
        app.store.setFingerprintEnabled(enabled)
        fingerprintEnabled=enabled
        isBiometricLocked=false
        message=if(enabled) "Fingerprint lock enabled. You will be asked to unlock when returning to the app."
          else "Fingerprint lock disabled."
    }

    fun unlockWithPin(pin:String) {
        if(!offlinePinEnabled || userId==null) return
        if(pinFailures>=5){message="Too many incorrect PIN attempts. Sign in online.";return}
        if(app.store.verifyOfflinePin(pin)) {
            pinFailures=0;offlineUnlocked=true;requiresAuthentication=false;isBiometricLocked=false
            message="Offline access unlocked."
        } else {
            pinFailures++
            message=if(pinFailures>=5)"Too many attempts. Sign in online." else "Incorrect offline PIN."
        }
    }
    fun enableOfflinePin(pin:String,confirm:String) {
        if(pin!=confirm) {message="PINs do not match.";return}
        if(pin.length !in 6..12 || !pin.all(Char::isDigit)) {message="Choose a 6 to 12 digit PIN.";return}
        viewModelScope.launch {
            busy=true
            try {
                kotlinx.coroutines.withContext(Dispatchers.Default){app.store.setOfflinePin(pin)}
                offlinePinEnabled=true;message="Offline PIN enabled for this device."
            } catch(e:Exception){message=e.message ?: "Unable to save offline PIN"}
            finally{busy=false}
        }
    }
    fun removeOfflinePin(pin:String) {
        if(!app.store.verifyOfflinePin(pin)) {message="Enter your current offline PIN.";return}
        app.store.removeOfflinePin();offlinePinEnabled=false
        message="Offline PIN removed."
    }
    private fun readReports(json:JSONObject?):List<ReportRow> {
        val rows=json?.optJSONArray("reports") ?: return emptyList()
        return (0 until rows.length()).map {i->rows.getJSONObject(i).let {
            ReportRow(it.getLong("id"),it.optString("reference"),it.optString("title"),it.optString("verification_status"))
        }}
    }
    private var observing: Job? = null

    init {
        parseCatalog(app.store.catalog())
        observe()
        if (userId != null) {
            refreshCatalog()
            refreshProfile()
        }
    }

    private fun parseCatalog(json: JSONObject?) {
        fun read(key: String): List<Choice> {
            val array=json?.optJSONArray(key) ?: return emptyList()
            return (0 until array.length()).map { i ->
                array.getJSONObject(i).let { Choice(it.getInt("id"), it.getString("name")) }
            }
        }
        lgas=read("lgas");sectors=read("sectors")
        val cat=json?.optJSONArray("categories")
        categories=if(cat==null)emptyList() else (0 until cat.length()).map{ i->
            cat.getJSONObject(i).let{CategoryChoice(it.optInt("id"),it.optString("name"),it.optString("type"))}
        }
        val com=json?.optJSONArray("communities")
        communities=if(com==null)emptyList() else (0 until com.length()).map{ i->
            com.getJSONObject(i).let{CommunityChoice(it.optInt("id"),it.optString("name"),it.optInt("lga_id"))}
        }
    }

    private fun observe() {
        observing?.cancel()
        val owner=userId ?: run { drafts=emptyList(); return }
        observing=viewModelScope.launch {
            repo.pending(owner).collectLatest { drafts=it }
        }
    }

    fun checkSimilar(title:String,lga:Int,community:String,sector:Int,type:String,date:String){
        if(busy)return
        if(title.trim().length<5 || lga<=0){
            message="Choose an LGA and enter a report title.";return
        }
        val token=app.store.token() ?: run{beginReauthentication();return}
        viewModelScope.launch {
            busy=true;similarMatches=emptyList()
            try{
                val request=JSONObject().put("title",title.trim()).put("lga_id",lga)
                    .put("community",community.trim()).put("sector_id",sector)
                    .put("type",type).put("observation_date",date)
                val list=app.api.similarReports(token,request).optJSONArray("matches")
                similarMatches=if(list==null)emptyList() else (0 until list.length()).map{ i->
                    list.getJSONObject(i).optString("title")
                }
                message=if(similarMatches.isEmpty())"No similar report detected."
                  else "Possible matches found. You may still submit a distinct observation."
            }catch(e:Exception){
                if(e is ApiFailure && e.code==401)beginReauthentication()
                else message="Similarity check unavailable. You can still save this report offline."
            }finally{busy=false}
        }
    }

    fun display(text: String) { message=text }
    fun clearMessage() { message="" }

    fun resetLogin() {
        loginChallenge=null
        message=""
    }

    fun checkAuthentication() {
        if(userId!=null && app.store.token()==null && !offlineUnlocked)requiresAuthentication=true
    }

    fun beginReauthentication() {
        app.store.invalidateToken()
        isBiometricLocked=false
        requiresAuthentication=true
        loginChallenge=null
        message="Sign in again with the same field account to resume syncing."
    }

    private fun networkError(error: Exception): String = when(error) {
        is UnknownHostException, is SocketTimeoutException, is java.net.ConnectException ->
            "Server unavailable. You can work with saved offline data."
        else -> if(error is ApiFailure) error.message?.take(160) ?: "Request failed"
            else "Connection unavailable. You can continue with saved offline data."
    }

    fun login(email: String, password: String) {
        if(busy) return
        viewModelScope.launch {
            busy=true
            message=""
            try {
                val result=app.api.login(email.trim(),password)
                if(result.optBoolean("two_factor_required")) {
                    val challenge=result.getString("challenge")
                    require(challenge.length==64) { "Invalid verification response" }
                    loginChallenge=challenge
                } else completeLogin(result)
            } catch(e:Exception) {message=networkError(e)}
            finally {busy=false}
        }
    }

    fun verifyOtp(otp: String) {
        if(busy) return
        val challenge=loginChallenge ?: return
        if(otp.length!=6 || !otp.all(Char::isDigit)) {
            message="Enter the 6-digit authenticator code."
            return
        }
        viewModelScope.launch {
            busy=true
            message=""
            try {
                completeLogin(app.api.verifyOtp(challenge,otp))
            } catch(e:Exception) {
                message=networkError(e)
                if(e is ApiFailure && (e.message?.contains("again",true)==true ||
                    e.message?.contains("expired",true)==true)) loginChallenge=null
            } finally {busy=false}
        }
    }

    private suspend fun completeLogin(result:JSONObject) {
        val token=result.getString("token")
        val user=result.getJSONObject("user")
        val newId=user.optLong("id")
        require(userId==null || userId==newId) {
            "This device contains pending reports for another account. Sign in with the original field account."
        }
        app.store.storeSession(token,user)
        requiresAuthentication=false
        isBiometricLocked=false
        fingerprintEnabled=app.store.fingerprintEnabled()
        offlinePinEnabled=app.store.offlinePinEnabled()
        offlineUnlocked=true
        loginChallenge=null
        userName=user.optString("name");userId=user.optLong("id")
        account=user
        app.store.cacheAccount(user)
        observe()
        try {
            val catalog=app.api.bootstrap(token)
            app.store.cacheCatalog(catalog);parseCatalog(catalog)
        } catch(e:Exception) {
            message="Signed in. Assigned areas will refresh when connected."
        }
        refreshProfile()
        refreshReports()
        SyncScheduler.enqueue(app)
    }

    fun refreshCatalog() {
        val token=app.store.token() ?: return
        viewModelScope.launch {
            refreshingCatalog=true
            try {
                val catalog=app.api.bootstrap(token)
                app.store.cacheCatalog(catalog);parseCatalog(catalog)
            } catch (e: Exception) {
                if (app.store.catalog()==null)message="Sign in online once to load your assigned LGAs."
            } finally {refreshingCatalog=false}
        }
    }

    fun refreshProfile() {
        val token=app.store.token() ?: return
        viewModelScope.launch {
            refreshingCatalog=true
            try {
                val p=app.api.profile(token).getJSONObject("user")
                account=p;app.store.cacheAccount(p)
            } catch(_:Exception) {} finally {refreshingCatalog=false}
        }
    }
    fun changePassword(current:String,next:String,confirmation:String) {
        if(next!=confirmation){message="New passwords do not match.";return}
        if(next.length<10){message="Use at least 10 characters.";return}
        val token=app.store.token() ?: run{message="Connect and sign in to change your password.";return}
        viewModelScope.launch {
            busy=true
            try { app.api.changePassword(token,current,next)
                message="Password updated successfully."
            }catch(e:Exception){message=networkError(e)}
            finally {busy=false}
        }
    }
    fun beginTwoFactor(password:String) {
        val token=app.store.token() ?: run{message="Connect to set up two-factor authentication.";return}
        viewModelScope.launch {
            busy=true;twoFactorSecret="";recoveryCodes=emptyList()
            try {
                val result=app.api.startTwoFactor(token,password)
                twoFactorSecret=result.getString("secret")
                message="Enter the setup key in your authenticator, then confirm its 6-digit code."
            }catch(e:Exception){message=networkError(e)}finally{busy=false}
        }
    }
    fun confirmTwoFactor(code:String) {
        val token=app.store.token() ?: return
        viewModelScope.launch {
            busy=true
            try {
                val result=app.api.confirmTwoFactor(token,code)
                val codes=result.getJSONArray("recovery_codes")
                recoveryCodes=(0 until codes.length()).map{codes.getString(it)}
                twoFactorSecret=""
                account=JSONObject(account.toString()).put("two_factor_enabled",true)
                app.store.cacheAccount(account)
                message="2FA enabled. Save the one-time recovery codes in a safe place."
            }catch(e:Exception){message=networkError(e)}finally{busy=false}
        }
    }
    fun disableTwoFactor(password:String,code:String) {
        val token=app.store.token() ?: run{message="Connect to disable two-factor authentication.";return}
        viewModelScope.launch {
            busy=true
            try {
                app.api.disableTwoFactor(token,password,code)
                recoveryCodes=emptyList();twoFactorSecret=""
                account=JSONObject(account.toString()).put("two_factor_enabled",false)
                app.store.cacheAccount(account)
                message="Two-factor authentication disabled."
            }catch(e:Exception){message=networkError(e)}finally{busy=false}
        }
    }

    fun logout() {
        if(busy) return
        viewModelScope.launch {
            val owner=userId ?: return@launch
            if(repo.dao.count(owner)>0) {
                message="Synchronize all pending field reports before signing out. Your evidence is protected."
                return@launch
            }
            // Revocation is best-effort; the token expires server-side even if temporarily offline.
            val token=app.store.token()
            if(token!=null) runCatching { app.api.logout(token) }
            app.store.clearSession()
            userName="";userId=null;lgas=emptyList();sectors=emptyList()
            reports=emptyList();selectedReport=null;observing?.cancel();drafts=emptyList()
            account=JSONObject();offlinePinEnabled=false;offlineUnlocked=false
            twoFactorSecret="";recoveryCodes=emptyList()
            restoredDraft=null;attachments=emptyList();requiresAuthentication=false
            fingerprintEnabled=false;isBiometricLocked=false
            message="Signed out."
        }
    }

    fun captureEvidence(uri: Uri, filename: String? = null, mime: String? = null,
                        onComplete: (() -> Unit)? = null) {
        if(busy) return
        viewModelScope.launch {
            busy=true
            try {
                if (attachments.size>=5) error("Maximum five evidence items")
                val detected=mime ?: app.contentResolver.getType(uri) ?: "application/octet-stream"
                attachments=attachments + repo.prepareEvidence(uri,filename,detected)
                message="Evidence encrypted and ready."
            } catch(e: Exception) { message=e.message ?: "Cannot attach evidence" }
            finally{onComplete?.invoke();busy=false}
        }
    }

    fun captureMany(uris: List<Uri>) {
        if(busy) return
        viewModelScope.launch {
            busy=true
            try {
                val space=(5-attachments.size).coerceAtLeast(0)
                for(uri in uris.take(space)) {
                    val mime=app.contentResolver.getType(uri) ?: "application/octet-stream"
                    attachments=attachments + repo.prepareEvidence(uri,null,mime)
                }
                if(uris.size>space) message="Only five supporting files can be attached."
            } catch(e:Exception){message=e.message ?: "Could not attach file"}
            finally {busy=false}
        }
    }

    fun editDraft(draftId: String,onReady:()->Unit) {
        if(busy)return
        viewModelScope.launch {
            busy=true
            try{
                val recovered=repo.restoreDraft(draftId)
                restoredDraft=recovered
                attachments=recovered.files
                message="Queued report loaded. Save changes to resume synchronization."
                onReady()
            }catch(e:Exception){message=e.message?:"Unable to restore this report"}
            finally{busy=false}
        }
    }

    fun cancelEditing() {
        val restored=restoredDraft ?: return
        val owner=userId ?: return
        viewModelScope.launch {
            busy=true
            try {
                val savedIds=restored.files.map { it.id }.toSet()
                repo.abandonUnused(attachments.filterNot { it.id in savedIds })
                repo.dao.mark(restored.id,owner,
                    if(restored.status=="editing")"retry" else restored.status,restored.error)
                if(restored.status in setOf("pending","retry","editing"))SyncScheduler.enqueue(app)
                attachments=emptyList()
                restoredDraft=null
                message="Original queued report was kept."
            } catch(e:Exception){message=e.message?:"Could not cancel changes"}
            finally{busy=false}
        }
    }

    fun retryDraft(draftId: String) {
        val owner=userId ?: return
        viewModelScope.launch {
            repo.dao.mark(draftId,owner,"retry",null)
            SyncScheduler.enqueue(app)
            message="Retry scheduled. The report remains encrypted on your device."
        }
    }

    fun removeEvidence(index: Int) {
        val removed=attachments.getOrNull(index) ?: return
        if(restoredDraft?.files?.none { it.id==removed.id }!=false)
            repo.abandonUnused(listOf(removed))
        attachments=attachments.filterIndexed { i,_ -> i!=index }
    }

    fun save(payload: JSONObject, onSaved: () -> Unit) {
        if(busy) return
        viewModelScope.launch {
            busy=true
            try {
                validatePayload(payload)
                restoredDraft?.let { payload.put("client_submission_id",it.id) }
                repo.queue(payload,attachments)
                restoredDraft=null
                attachments=emptyList()
                similarMatches=emptyList()
                message="Report encrypted on this device. Automatic synchronization queued."
                onSaved()
            } catch(e: Exception){message=e.message ?: "Unable to save report"}
            finally{busy=false}
        }
    }

    fun synchronize() {
        SyncScheduler.enqueue(app)
        message="Synchronization scheduled for when a network is available."
    }

    fun refreshReports() {
        if(refreshingReports)return
        val token=app.store.token() ?: run {
            message="Showing saved reports. Sign in online to refresh.";return
        }
        viewModelScope.launch {
            refreshingReports=true
            try {
                val response=app.api.reports(token)
                app.store.cacheReports(response)
                reports=readReports(response)
                message="Reports updated."
            }catch(e:Exception){
                message="Offline: showing last saved reports."
            }finally {refreshingReports=false}
        }
    }

    fun selectReport(id: Long) {
        val token=app.store.token()
        if(token==null) {
            val cached=app.store.report(id)
            if(cached!=null) {
                val item=cached.getJSONObject("report")
                selectedReport=ReportDetails(id,item.optString("reference"),item.optString("title"),
                    item.optString("verification_status"),item.optString("description"),emptyList(),
                    cached.optJSONObject("fields") ?: JSONObject(),cached.optInt("version",0),false)
            }else message="Open this report online once to save its details."
            return
        }
        viewModelScope.launch {
            try {
                val response=app.api.report(token,id)
                app.store.cacheReport(id,response)
                val item=response.getJSONObject("report")
                val follows=item.optJSONArray("followups")
                selectedReport=ReportDetails(id,item.optString("reference"),item.optString("title"),
                    item.optString("verification_status"),item.optString("description"),
                    if(follows==null) emptyList() else (0 until follows.length())
                        .map { follows.getJSONObject(it).optString("note") },
                    response.optJSONObject("fields") ?: JSONObject(),
                    response.optInt("version",0),response.optBoolean("editable",false))
            } catch(e:Exception){
                val saved=app.store.report(id)
                if(saved!=null){
                    val item=saved.getJSONObject("report")
                    val follows=item.optJSONArray("followups")
                    selectedReport=ReportDetails(id,item.optString("reference"),item.optString("title"),
                        item.optString("verification_status"),item.optString("description"),
                        if(follows==null) emptyList() else (0 until follows.length())
                            .map{follows.getJSONObject(it).optString("note")},
                        saved.optJSONObject("fields") ?: JSONObject(),
                        saved.optInt("version",0),false)
                    message="Showing last saved report."
                }else message="This report has not been saved on this device."
            }
        }
    }

    fun reviseSubmittedReport(report:ReportDetails,payload:JSONObject,onSaved:()->Unit){
        if(busy)return
        val token=app.store.token() ?: run {beginReauthentication();return}
        viewModelScope.launch{
            busy=true
            try {
                require(report.editable) {"This report is locked for editing."}
                validatePayload(payload)
                require(payload.optString("reason").trim().length in 5..2000){
                    "Explain the reason for this correction (at least five characters)."
                }
                payload.put("version",report.version)
                val result=app.api.reviseReport(token,report.id,payload)
                message=result.optString("message","Report changes saved.")
                onSaved()
                selectReport(report.id)
                refreshReports()
            }catch(e:Exception){
                if(e is ApiFailure && e.code==401)beginReauthentication()
                else message=networkError(e)
            }finally{busy=false}
        }
    }

    fun respondToClarification(text: String) {
        val report=selectedReport ?: return
        val token=app.store.token() ?: return
        viewModelScope.launch {
            try {
                require(text.trim().length >= 5) { "Enter at least five characters" }
                app.api.clarification(token,report.id,text.trim())
                message="Clarification response submitted."
                selectReport(report.id)
                refreshReports()
            }catch(e:Exception){message=e.message ?: "Cannot submit response"}
        }
    }

    fun draftTitle(draft: Draft): String =
        runCatching { repo.decode(draft).optString("title","Untitled field report") }.getOrDefault("Locked draft")
}

internal fun validatePayload(value: JSONObject) {
    require(value.optString("title").trim().length in 5..200) { "Title must be between 5 and 200 characters." }
    require(value.optString("description").trim().length in 15..10000) { "Description must contain at least 15 characters." }
    require(value.optString("community").trim().isNotBlank()) { "Community is required." }
    require(value.optInt("lga_id")>0 && value.optInt("sector_id")>0) { "Choose a valid LGA and sector." }
    require(value.optString("observation_date").matches(Regex("\\d{4}-\\d{2}-\\d{2}"))) { "Use a valid YYYY-MM-DD date." }
    require(value.optString("type") in setOf("public_project","governance_issue")) { "Unknown report type." }
    require(value.optString("progress_status") in setOf(
        "observed","not_started","ongoing","stalled","completed","resolved","other")) {
        "Choose a valid progress status."
    }
    require(runCatching {
        val date=java.time.LocalDate.parse(value.optString("observation_date"))
        !date.isAfter(java.time.LocalDate.now())
    }.getOrDefault(false)) { "Observation date cannot be in the future." }
    val lat=value.optString("latitude").trim()
    val lon=value.optString("longitude").trim()
    require((lat.isBlank() && lon.isBlank()) || (lat.isNotBlank() && lon.isNotBlank())) {
        "Provide both GPS coordinates, or leave both blank."
    }
    if(lat.isNotBlank()) {
        require(lat.toDoubleOrNull()?.let{it in -90.0..90.0}==true &&
            lon.toDoubleOrNull()?.let{it in -180.0..180.0}==true) {
            "GPS coordinates are outside the permitted range."
        }
    }
}
