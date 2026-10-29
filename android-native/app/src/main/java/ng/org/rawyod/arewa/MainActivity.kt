package ng.org.rawyod.arewa

import android.Manifest
import android.content.Context
import android.net.Uri
import android.webkit.WebView
import android.webkit.WebViewClient
import android.webkit.WebResourceRequest
import android.webkit.CookieManager
import android.webkit.WebSettings
import android.os.Bundle
import androidx.fragment.app.FragmentActivity
import androidx.activity.viewModels
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.activity.compose.setContent
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.graphics.RectangleShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.core.content.FileProvider
import androidx.lifecycle.viewmodel.compose.viewModel
import ng.org.rawyod.arewa.ui.*
import org.json.JSONObject
import java.io.File
import java.time.LocalDate
import java.util.UUID

private val green=Color(0xFF17543B)
private val gold=Color(0xFFAE8131)
private val ink=Color(0xFF1D2D25)
private val paper=Color(0xFFF4F7F3)

class MainActivity:FragmentActivity() {
    private val fieldVm:FieldViewModel by viewModels()
    private var biometricNotice by mutableStateOf("")
    private val fingerprintAvailable: Boolean
        get() = BiometricManager.from(this).canAuthenticate(
            BiometricManager.Authenticators.BIOMETRIC_STRONG
        ) == BiometricManager.BIOMETRIC_SUCCESS

    private fun authenticateFingerprint(title:String, onSuccess:()->Unit) {
        if(!fingerprintAvailable) {
            biometricNotice="Strong fingerprint authentication is unavailable. Check your phone's fingerprint settings or sign in with your account password."
            return
        }
        biometricNotice=""
        val prompt=BiometricPrompt(this,ContextCompat.getMainExecutor(this),
            object:BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result:BiometricPrompt.AuthenticationResult) {
                    super.onAuthenticationSucceeded(result)
                    biometricNotice=""
                    onSuccess()
                }
                override fun onAuthenticationError(errorCode:Int,errString:CharSequence) {
                    super.onAuthenticationError(errorCode,errString)
                    biometricNotice=errString.toString()
                }
                override fun onAuthenticationFailed() {
                    super.onAuthenticationFailed()
                    biometricNotice="Fingerprint not recognized. Try again."
                }
            })
        prompt.authenticate(
            BiometricPrompt.PromptInfo.Builder()
                .setTitle(title)
                .setSubtitle("Use an enrolled fingerprint to access your field reports")
                .setAllowedAuthenticators(BiometricManager.Authenticators.BIOMETRIC_STRONG)
                .setNegativeButtonText("Cancel")
                .build()
        )
    }
    override fun onStop() {
        super.onStop()
        fieldVm.lockOnBackground()
    }
    override fun onCreate(savedInstanceState:Bundle?){
        super.onCreate(savedInstanceState)
        window.statusBarColor=android.graphics.Color.rgb(23,84,59)
        setContent {
            MaterialTheme(
                colorScheme=lightColorScheme(primary=green,onPrimary=Color.White,
                    secondary=gold,background=paper,surface=Color.White,onSurface=ink),
                shapes=Shapes(small=RoundedCornerShape(0.dp),medium=RoundedCornerShape(0.dp),
                    large=RoundedCornerShape(0.dp))
            ) { FieldApplication(fieldVm,fingerprintAvailable,biometricNotice,::authenticateFingerprint) }
        }
    }
}

@Composable
private fun FieldApplication(
    vm:FieldViewModel,
    fingerprintAvailable:Boolean,
    biometricNotice:String,
    authenticate:(String,()->Unit)->Unit
) {
    val user=vm.userId
    var tab by remember { mutableIntStateOf(0) }
    var guestPublic by remember { mutableStateOf(false) }
    LaunchedEffect(user,vm.drafts){ vm.checkAuthentication() }
    Scaffold(
        topBar={
            Surface(color=green,contentColor=Color.White){
                Column(Modifier.fillMaxWidth().padding(horizontal=18.dp,vertical=15.dp)){
                    Text("AREWA CIVIC TRACKER",style=MaterialTheme.typography.titleMedium,fontWeight=FontWeight.Bold)
                    Text("RAWYOD • VOICES TO VOTES",style=MaterialTheme.typography.labelSmall)
                }
            }
        },
        bottomBar={
            if(user!=null && !vm.isBiometricLocked && !vm.requiresAuthentication) NavigationBar(containerColor=Color.White){
                listOf("New report","Outbox","My reports","Settings").forEachIndexed{index,label->
                    NavigationBarItem(selected=tab==index,onClick={
                        tab=index
                        if(index==2)vm.refreshReports()
                    },icon={Text(when(index){0->"✎";1->"◷";2->"▤";else->"⚙"})},
                        label={Text(if(index==1)"Outbox (${vm.drafts.size})" else label)})
                }
            }
        }
    ) { inner ->
        Column(Modifier.fillMaxSize().padding(inner)){
            if(!vm.isBiometricLocked && vm.message.isNotBlank()) {
                Surface(color=Color(0xFFFFF3D3)){
                    Row(Modifier.fillMaxWidth().padding(12.dp),
                        horizontalArrangement=Arrangement.SpaceBetween,
                        verticalAlignment=Alignment.Top){
                        Text(vm.message,Modifier.weight(1f),color=ink)
                        TextButton(onClick=vm::clearMessage){Text("Dismiss")}
                    }
                }
            }
            when {
                (user==null || vm.requiresAuthentication) && guestPublic ->
                    PublicGuestScreen(onBack={guestPublic=false})
                user==null || vm.requiresAuthentication ->
                    LoginScreen(vm,onPublicView={guestPublic=true})
                vm.isBiometricLocked -> FingerprintLockScreen(
                    biometricNotice,
                    onUnlock={authenticate("Unlock Arewa Civic Tracker",vm::unlockWithFingerprint)},
                    onPassword=vm::beginReauthentication
                )
                tab==0 -> FormScreen(vm)
                tab==1 -> OutboxScreen(vm,onEdit={tab=0})
                tab==2 -> ReportsScreen(vm)
                else -> SecuritySettingsScreen(vm,fingerprintAvailable,biometricNotice,authenticate)
            }
        }
    }
}

@Composable
private fun FingerprintLockScreen(notice:String,onUnlock:()->Unit,onPassword:()->Unit) {
    Column(Modifier.fillMaxSize().padding(22.dp),
        verticalArrangement=Arrangement.Center,
        horizontalAlignment=Alignment.CenterHorizontally){
        Text("App locked",style=MaterialTheme.typography.headlineSmall,fontWeight=FontWeight.Bold)
        Spacer(Modifier.height(10.dp))
        Text("Use your fingerprint to unlock your field reports and encrypted drafts.",
            color=Color.DarkGray)
        Spacer(Modifier.height(18.dp))
        Button(onClick=onUnlock,shape=RectangleShape,modifier=Modifier.fillMaxWidth()){
            Text("Unlock with fingerprint")
        }
        OutlinedButton(onClick=onPassword,shape=RectangleShape,modifier=Modifier.fillMaxWidth()){
            Text("Sign in with account password")
        }
        if(notice.isNotBlank())Text(notice,color=MaterialTheme.colorScheme.error)
        Text("Password sign-in requires internet access. Your offline drafts remain saved.",
            style=MaterialTheme.typography.bodySmall,color=Color.DarkGray)
    }
}

@Composable
private fun SecuritySettingsScreen(
    vm:FieldViewModel,
    available:Boolean,
    notice:String,
    authenticate:(String,()->Unit)->Unit
) {
    Content("App settings","Protect field reports stored on this phone.") {
        Text("Fingerprint security",style=MaterialTheme.typography.titleMedium,fontWeight=FontWeight.Bold)
        Text(if(vm.fingerprintEnabled)"Enabled for your account" else "Not enabled",
            color=if(vm.fingerprintEnabled)green else Color.DarkGray)
        Text("Once enabled, a fingerprint is required when reopening or returning to the app. Your account password remains available as a fallback.",
            style=MaterialTheme.typography.bodyMedium)
        if(!available) {
            Text("A compatible enrolled fingerprint is not available on this device. Add a fingerprint in Android Settings first.",
                color=MaterialTheme.colorScheme.error)
            if(vm.fingerprintEnabled) {
                Text("After signing in with your account password, you may remove this unavailable device lock.",
                    style=MaterialTheme.typography.bodySmall)
                OutlinedButton(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                    onClick={vm.changeFingerprintSetting(false)}) {
                    Text("Remove fingerprint lock after password sign-in")
                }
            }
        } else {
            Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                onClick={
                    val enable=!vm.fingerprintEnabled
                    authenticate(if(enable)"Enable fingerprint lock" else "Disable fingerprint lock"){
                        vm.changeFingerprintSetting(enable)
                    }
                }) {Text(if(vm.fingerprintEnabled)"Disable fingerprint lock" else "Enable fingerprint lock")}
            if(vm.fingerprintEnabled)
                OutlinedButton(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                    onClick=vm::lockOnBackground){Text("Lock app now")}
        }
        if(notice.isNotBlank())Text(notice,color=MaterialTheme.colorScheme.error)
        HorizontalDivider()
        Text("Account security",style=MaterialTheme.typography.titleMedium,fontWeight=FontWeight.SemiBold)
        Text("Fingerprint unlock only opens the local app. Password and optional two-factor verification are still handled by the RAWYOD server.",
            style=MaterialTheme.typography.bodySmall)
        OutlinedButton(shape=RectangleShape,onClick=vm::logout,modifier=Modifier.fillMaxWidth()){
            Text("Sign out")
        }
    }
}

@Composable
private fun Content(title:String,subtitle:String,body:@Composable ColumnScope.()->Unit) {
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())
        .padding(horizontal=16.dp,vertical=18.dp),
        verticalArrangement=Arrangement.spacedBy(12.dp)){
        Text(title,style=MaterialTheme.typography.headlineSmall,fontWeight=FontWeight.Bold)
        Text(subtitle,color=Color.DarkGray,style=MaterialTheme.typography.bodyMedium)
        body()
        Spacer(Modifier.height(18.dp))
    }
}

@Composable
private fun PublicGuestScreen(onBack:()->Unit) {
    var webView by remember { mutableStateOf<WebView?>(null) }
    DisposableEffect(Unit) {
        onDispose { webView?.destroy(); webView=null }
    }
    Column(Modifier.fillMaxSize()) {
        Row(
            Modifier.fillMaxWidth().background(Color.White).padding(horizontal=16.dp,vertical=9.dp),
            verticalAlignment=Alignment.CenterVertically,
            horizontalArrangement=Arrangement.SpaceBetween
        ) {
            Text("Public transparency",fontWeight=FontWeight.Bold)
            TextButton(onClick=onBack) {Text("Back to sign-in")}
        }
        AndroidView(
            modifier=Modifier.fillMaxSize(),
            factory={context->
                WebView(context).apply {
                    settings.javaScriptEnabled=true // Required by the read-only Leaflet map.
                    settings.domStorageEnabled=false
                    settings.allowFileAccess=false
                    settings.allowContentAccess=false
                    settings.javaScriptCanOpenWindowsAutomatically=false
                    settings.mixedContentMode=WebSettings.MIXED_CONTENT_NEVER_ALLOW
                    CookieManager.getInstance().setAcceptThirdPartyCookies(this,false)
                    webViewClient=object:WebViewClient(){
                        override fun shouldOverrideUrlLoading(view:WebView, request:WebResourceRequest):Boolean {
                            val uri=request.url
                            return uri.scheme!="https" || uri.host!="arewa.kasua.ng" ||
                                !uri.path.orEmpty().startsWith("/transparency")
                        }
                    }
                    loadUrl("https://arewa.kasua.ng/transparency")
                    webView=this
                }
            }
        )
    }
}

@Composable
private fun LoginScreen(vm:FieldViewModel,onPublicView:()->Unit) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var otp by remember { mutableStateOf("") }
    val challenge=vm.loginChallenge
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())
        .padding(horizontal=16.dp,vertical=22.dp),
        verticalArrangement=Arrangement.spacedBy(14.dp)) {
        Text(if(challenge==null)"Sign in" else "Verify your account",
            style=MaterialTheme.typography.headlineSmall,fontWeight=FontWeight.Bold)
        if(challenge==null) {
            Surface(color=Color(0xFFF0F6EF),modifier=Modifier.fillMaxWidth()) {
                Column(Modifier.padding(14.dp),verticalArrangement=Arrangement.spacedBy(7.dp)) {
                    Text("Visiting without an account?",fontWeight=FontWeight.Bold)
                    Text("Explore RAWYOD-approved public project and community reports. No registration or sign-in required.",
                        style=MaterialTheme.typography.bodySmall)
                    OutlinedButton(onClick=onPublicView,shape=RectangleShape,modifier=Modifier.fillMaxWidth()) {
                        Text("Browse public reports")
                    }
                }
            }
            HorizontalDivider()
            Text("RAWYOD staff sign-in",style=MaterialTheme.typography.titleMedium)
            OutlinedTextField(email,{email=it},label={Text("Email")},singleLine=true,
                shape=RectangleShape,
                keyboardOptions=KeyboardOptions(keyboardType=KeyboardType.Email),
                modifier=Modifier.fillMaxWidth())
            OutlinedTextField(password,{password=it},label={Text("Password")},singleLine=true,
                shape=RectangleShape,visualTransformation=PasswordVisualTransformation(),
                modifier=Modifier.fillMaxWidth())
            Button(shape=RectangleShape,onClick={vm.login(email,password)},
                enabled=!vm.busy && email.isNotBlank() && password.isNotBlank(),
                modifier=Modifier.fillMaxWidth()) {
                if(vm.busy) CircularProgressIndicator(Modifier.size(18.dp),
                    strokeWidth=2.dp,color=Color.White)
                Spacer(Modifier.width(6.dp));Text("Sign in")
            }
        } else {
            Text("Enter the 6-digit code from your authenticator.",
                style=MaterialTheme.typography.bodyMedium)
            OutlinedTextField(otp,{otp=it.filter(Char::isDigit).take(6)},
                label={Text("Authenticator code")},singleLine=true,shape=RectangleShape,
                keyboardOptions=KeyboardOptions(keyboardType=KeyboardType.NumberPassword),
                modifier=Modifier.fillMaxWidth())
            Button(shape=RectangleShape,onClick={vm.verifyOtp(otp)},
                enabled=!vm.busy && otp.length==6,modifier=Modifier.fillMaxWidth()){
                if(vm.busy) CircularProgressIndicator(Modifier.size(18.dp),
                    strokeWidth=2.dp,color=Color.White)
                else Text("Verify and sign in")
            }
            TextButton(onClick={vm::resetLogin},enabled=!vm.busy) { Text("Back to sign in") }
        }
    }
}

@Composable
internal fun ChoiceField(label:String,items:List<Choice>,value:Int,onChoose:(Int)->Unit) {
    var expanded by remember { mutableStateOf(false) }
    Column {
        Text(label,style=MaterialTheme.typography.labelMedium)
        Box {
            OutlinedButton(shape=RectangleShape,onClick={expanded=true},modifier=Modifier.fillMaxWidth()){
                Text(items.find{it.id==value}?.name ?: "Select $label",
                    modifier=Modifier.weight(1f))
                Text("⌄")
            }
            DropdownMenu(expanded=expanded,onDismissRequest={expanded=false}) {
                items.forEach { item->
                    DropdownMenuItem(text={Text(item.name)},onClick={
                        onChoose(item.id);expanded=false
                    })
                }
            }
        }
    }
}

@Composable
private fun FormScreen(vm:FieldViewModel) {
    val context=LocalContext.current
    val editing=vm.restoredDraft
    var type by remember { mutableStateOf("public_project") }
    var lga by remember { mutableIntStateOf(0) }
    var sector by remember { mutableIntStateOf(0) }
    var category by remember { mutableIntStateOf(0) }
    var title by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var community by remember { mutableStateOf("") }
    var observation by remember { mutableStateOf("") }
    var dutyBearers by remember { mutableStateOf("") }
    var date by remember { mutableStateOf(LocalDate.now().toString()) }
    var progress by remember { mutableStateOf("ongoing") }
    var latitude by remember { mutableStateOf("") }
    var longitude by remember { mutableStateOf("") }
    var cameraFile by remember { mutableStateOf<File?>(null) }
    LaunchedEffect(editing?.id){
        val data=editing?.payload ?: return@LaunchedEffect
        type=data.optString("type","public_project")
        lga=data.optInt("lga_id")
        sector=data.optInt("sector_id")
        category=data.optInt("report_category_id")
        title=data.optString("title")
        description=data.optString("description")
        community=data.optString("community")
        observation=data.optString("observations")
        dutyBearers=data.optString("duty_bearers")
        date=data.optString("observation_date",LocalDate.now().toString())
        progress=data.optString("progress_status","ongoing")
        latitude=data.optString("latitude")
        longitude=data.optString("longitude")
    }

    val camera=rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { ok->
        val file=cameraFile
        if(ok && file!=null)vm.captureEvidence(Uri.fromFile(file),file.name,"image/jpeg"){
            file.delete()
        } else file?.delete()
    }
    val picker=rememberLauncherForActivityResult(ActivityResultContracts.OpenMultipleDocuments()){uris->
        vm.captureMany(uris)
    }
    val gpsPermission=rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ){results->
        if(results.values.any{it}) LocationOnce.capture(context){location->
            if(location!=null){
                latitude=location.latitude.toString()
                longitude=location.longitude.toString()
                vm.display("GPS captured. Review coordinates before saving.")
            } else vm.display("A current GPS fix was unavailable. You can enter coordinates.")
        } else vm.display("Location permission denied; GPS is optional.")
    }

    Content(if(editing!=null)"Edit queued report" else "New field report",
        if(editing!=null)"Correct this encrypted draft before retrying upload."
        else "Saved reports are encrypted and queued when there is no network.") {
        if(editing!=null) OutlinedButton(shape=RectangleShape,onClick=vm::cancelEditing,
            modifier=Modifier.fillMaxWidth()){ Text("Cancel changes and keep original") }
        if(vm.lgas.isEmpty())Text("No assigned LGA. Contact your RAWYOD Administrator.",
            color=MaterialTheme.colorScheme.error)
        Text("Report category",fontWeight=FontWeight.SemiBold)
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            FilterChip(shape=RectangleShape,selected=type=="public_project",onClick={type="public_project"},
                label={Text("Public project")})
            FilterChip(shape=RectangleShape,selected=type=="governance_issue",onClick={type="governance_issue"},
                label={Text("Governance issue")})
        }
        ChoiceField("Assigned LGA",vm.lgas,lga){lga=it}
        ChoiceField("Sector",vm.sectors,sector){sector=it}
        val availableCategories=vm.categories.filter{it.type==type}
        if(availableCategories.isNotEmpty())ChoiceField("Report Category",
           listOf(Choice(0,"No category"))+availableCategories.map{Choice(it.id,it.name)},
           if(availableCategories.any{it.id==category})category else 0){category=it}
        val knownCommunities=vm.communities.filter{it.lgaId==lga}
        if(knownCommunities.isNotEmpty())ChoiceField("Known Community (Optional)",
           listOf(Choice(0,"Enter Manually"))+knownCommunities.map{Choice(it.id,it.name)},0){id->
           if(id!=0)community=knownCommunities.first{it.id==id}.name
        }
        OutlinedTextField(community,{community=it},label={Text("Community *")},modifier=Modifier.fillMaxWidth())
        OutlinedTextField(title,{title=it},label={Text("Report title *")},modifier=Modifier.fillMaxWidth())
        OutlinedTextField(description,{description=it},label={Text("Description *")},modifier=Modifier.fillMaxWidth(),
            minLines=3)
        OutlinedTextField(observation,{observation=it},label={Text("Observations")},modifier=Modifier.fillMaxWidth())
        OutlinedTextField(dutyBearers,{dutyBearers=it},label={Text("Duty bearers / stakeholders")},
            modifier=Modifier.fillMaxWidth())
        OutlinedTextField(date,{date=it},label={Text("Observation date (YYYY-MM-DD)")},
            modifier=Modifier.fillMaxWidth())
        ChoiceField("Progress",listOf(
            Choice(1,"Observed"),Choice(2,"Not started"),Choice(3,"Ongoing"),
            Choice(4,"Stalled"),Choice(5,"Completed"),Choice(6,"Resolved"),
            Choice(7,"Other")
        ),listOf("observed","not_started","ongoing","stalled","completed","resolved","other")
            .indexOf(progress)+1){i->
                progress=listOf("observed","not_started","ongoing","stalled","completed","resolved","other")[i-1]
            }
        HorizontalDivider()
        Text("GPS and supporting evidence",fontWeight=FontWeight.Bold)
        OutlinedButton(shape=RectangleShape,onClick={
            gpsPermission.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION))
        },modifier=Modifier.fillMaxWidth()){
            Text("GPS");Spacer(Modifier.width(6.dp))
            Text("Capture current GPS location")
        }
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            OutlinedTextField(latitude,{latitude=it},label={Text("Latitude")},
                modifier=Modifier.weight(1f))
            OutlinedTextField(longitude,{longitude=it},label={Text("Longitude")},
                modifier=Modifier.weight(1f))
        }
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            OutlinedButton(shape=RectangleShape,onClick={
                val target=File(context.cacheDir,"camera").also {it.mkdirs()}
                val file=File(target,"field-${UUID.randomUUID()}.jpg")
                cameraFile=file
                val uri=FileProvider.getUriForFile(context,
                    context.packageName+".fileprovider",file)
                camera.launch(uri)
            },enabled=vm.attachments.size<5 && !vm.busy,modifier=Modifier.weight(1f)){
                Text("Camera")
            }
            OutlinedButton(shape=RectangleShape,onClick={picker.launch(arrayOf(
                "image/jpeg","image/png","image/webp","application/pdf"
            ))},enabled=vm.attachments.size<5 && !vm.busy,modifier=Modifier.weight(1f)){
                Text("Add evidence")
            }
        }
        Text("Up to 5 photographs or PDFs, maximum 5 MB each.",
            style=MaterialTheme.typography.bodySmall)
        vm.attachments.forEachIndexed{index,file->
            Row(Modifier.fillMaxWidth(),
                horizontalArrangement=Arrangement.SpaceBetween,
                verticalAlignment=Alignment.CenterVertically){
                Text(file.filename.take(36),modifier=Modifier.weight(1f))
                TextButton(onClick={vm.removeEvidence(index)}){Text("Remove")}
            }
        }
        OutlinedButton(shape=RectangleShape,onClick={
            vm.checkSimilar(title,lga,community,sector,type,date)
        },enabled=!vm.busy && title.trim().length>=5 && lga>0,
            modifier=Modifier.fillMaxWidth()){Text("Check Similar Reports")}
        vm.similarMatches.forEach{ match->
            Text("Possible match: "+match,style=MaterialTheme.typography.bodySmall,
                color=Color(0xFF795B2C))
        }
        Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),enabled=!vm.busy && vm.lgas.isNotEmpty(),
            onClick={
                try {
                    val json=JSONObject()
                        .put("type",type).put("title",title.trim())
                        .put("description",description.trim()).put("community",community.trim())
                        .put("lga_id",lga).put("sector_id",sector)
                        .put("report_category_id",
                          if(category>0 && vm.categories.any{it.id==category && it.type==type})
                              category else JSONObject.NULL)
                        .put("observation_date",date).put("progress_status",progress)
                        .put("observations",observation).put("duty_bearers",dutyBearers)
                    if(latitude.isNotBlank() && longitude.isNotBlank()){
                        json.put("latitude",latitude.toDouble())
                        json.put("longitude",longitude.toDouble())
                    }
                    vm.save(json){
                        title=""; description="";community="";observation="";dutyBearers=""
                        latitude="";longitude=""
                    }
                }catch(e:Exception){vm.display(e.message ?: "Please correct the fields")}
            }){
            Text("Save");Spacer(Modifier.width(6.dp))
            Text(if(vm.busy)"Saving..." else
                if(editing!=null)"Save changes and queue" else "Encrypt and queue report")
        }
    }
}

@Composable
private fun OutboxScreen(vm:FieldViewModel,onEdit:()->Unit) {
    Content("Offline outbox","Encrypted reports remain here until synchronization succeeds.") {
        Row(Modifier.fillMaxWidth(),horizontalArrangement=Arrangement.SpaceBetween){
            Text("${vm.drafts.size} report(s) on device")
            TextButton(onClick={vm::synchronize}){Text("Synchronize")}
        }
        vm.drafts.forEach { draft->
            Surface(border=BorderStroke(1.dp,Color(0xFFCBD7CC))) {
                Column(Modifier.fillMaxWidth().padding(12.dp)) {
                    Text(vm.draftTitle(draft),fontWeight=FontWeight.Bold)
                    Text(draft.status.replaceFirstChar{it.uppercase()},
                        color=if(draft.status=="rejected")MaterialTheme.colorScheme.error else green)
                    if(!draft.lastError.isNullOrBlank()) Text(draft.lastError!!,
                        style=MaterialTheme.typography.bodySmall)
                    Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
                        TextButton(onClick={vm.editDraft(draft.id,onEdit)},enabled=!vm.busy){
                            Text(if(draft.status=="editing")"Resume editing" else "Edit draft")
                        }
                        if(draft.status=="rejected" || draft.status=="retry") {
                            TextButton(onClick={vm.retryDraft(draft.id)}) {Text("Retry upload")}
                        }
                    }
                    if(draft.lastError?.contains("Sign in again",ignoreCase=true)==true){
                        TextButton(onClick=vm::beginReauthentication){
                            Text("Reauthenticate")
                        }
                    }
                }
            }
        }
        if(vm.drafts.isEmpty())Text("No pending reports. Submitted reports appear in My reports.")
        Text("Reports are not deleted when your token expires. Sign in again to resume syncing.",
            style=MaterialTheme.typography.bodySmall)
    }
}

@Composable
private fun ReportsScreen(vm:FieldViewModel) {
    var response by remember { mutableStateOf("") }
    var editingReport by remember { mutableStateOf<Long?>(null) }
    Content("My submitted reports","Review verification status and respond to MEAL clarification requests.") {
        OutlinedButton(shape=RectangleShape,onClick=vm::refreshReports,modifier=Modifier.fillMaxWidth()){
            Text("Refresh from server")
        }
        vm.reports.forEach{report->
            Surface(border=BorderStroke(1.dp,Color(0xFFCBD7CC))) {
                Column(Modifier.fillMaxWidth().clickable { editingReport=null;vm.selectReport(report.id) }
                    .padding(12.dp)){
                    Text(report.title,fontWeight=FontWeight.Bold)
                    Text("${report.reference}  •  ${report.status.replace('_',' ')}",
                        style=MaterialTheme.typography.labelMedium)
                }
            }
        }
        vm.selectedReport?.let{detail->
            HorizontalDivider()
            if(editingReport==detail.id && detail.editable) {
                SubmittedRevisionForm(vm,detail,
                  onCancel={editingReport=null},onSaved={editingReport=null})
            } else {
            Text(detail.title,style=MaterialTheme.typography.titleLarge)
            Text(detail.description)
            Text("Status: ${detail.status.replace('_',' ')}")
            detail.followups.forEach{Text("Follow-up: "+it)}
            if(detail.editable){
                OutlinedButton(shape=RectangleShape,onClick={editingReport=detail.id},
                    modifier=Modifier.fillMaxWidth()){Text("Edit Submitted Report")}
            }
            if(detail.status=="clarification_requested"){
                OutlinedTextField(response,{response=it},label={Text("Clarification response")},
                    modifier=Modifier.fillMaxWidth(),minLines=3)
                Button(shape=RectangleShape,onClick={
                    vm.respondToClarification(response);response=""
                },enabled=response.length>=5){Text("Submit clarification")}
            }
            }
        }
        OutlinedButton(shape=RectangleShape,onClick=vm::logout,modifier=Modifier.fillMaxWidth()){
            Text("Sign out")
        }
    }
}
