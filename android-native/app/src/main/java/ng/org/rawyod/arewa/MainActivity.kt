package ng.org.rawyod.arewa

import android.Manifest
import android.content.Context
import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
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

class MainActivity:ComponentActivity() {
    override fun onCreate(savedInstanceState:Bundle?){
        super.onCreate(savedInstanceState)
        window.statusBarColor=android.graphics.Color.rgb(23,84,59)
        setContent {
            MaterialTheme(
                colorScheme=lightColorScheme(primary=green,onPrimary=Color.White,
                    secondary=gold,background=paper,surface=Color.White,onSurface=ink),
                shapes=Shapes(small=RoundedCornerShape(0.dp),medium=RoundedCornerShape(0.dp),
                    large=RoundedCornerShape(0.dp))
            ) { FieldApplication() }
        }
    }
}

@Composable
private fun FieldApplication(vm:FieldViewModel=viewModel()) {
    val user=vm.userId
    var tab by remember { mutableIntStateOf(0) }
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
            if(user!=null) NavigationBar(containerColor=Color.White){
                listOf("New report","Outbox","My reports").forEachIndexed{index,label->
                    NavigationBarItem(selected=tab==index,onClick={
                        tab=index
                        if(index==2)vm.refreshReports()
                    },icon={Text(if(index==0)"✎" else if(index==1)"◷" else "▤")},
                        label={Text(if(index==1)"Outbox (${vm.drafts.size})" else label)})
                }
            }
        }
    ) { inner ->
        Column(Modifier.fillMaxSize().padding(inner)){
            if(vm.message.isNotBlank()) {
                Surface(color=Color(0xFFFFF3D3)){
                    Row(Modifier.fillMaxWidth().padding(12.dp),
                        horizontalArrangement=Arrangement.SpaceBetween,
                        verticalAlignment=Alignment.Top){
                        Text(vm.message,Modifier.weight(1f),color=ink)
                        TextButton(onClick=vm::clearMessage){Text("Dismiss")}
                    }
                }
            }
            if(user==null) LoginScreen(vm)
            else when(tab){
                0->FormScreen(vm)
                1->OutboxScreen(vm)
                else->ReportsScreen(vm)
            }
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
private fun LoginScreen(vm:FieldViewModel) {
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
            OutlinedTextField(email,{email=it},label={Text("Email")},singleLine=true,
                shape=RectangleShape,
                keyboardOptions=KeyboardOptions(keyboardType=KeyboardType.Email),
                modifier=Modifier.fillMaxWidth())
            OutlinedTextField(password,{password=it},label={Text("Password")},singleLine=true,
                shape=RectangleShape,visualTransformation=PasswordVisualTransformation(),
                modifier=Modifier.fillMaxWidth())
            Button(onClick={vm.login(email,password)},shape=RectangleShape,
                enabled=!vm.busy && email.isNotBlank() && password.isNotBlank(),
                modifier=Modifier.fillMaxWidth()) {
                if(vm.busy) CircularProgressIndicator(Modifier.size(18.dp),
                    strokeWidth=2.dp,color=Color.White)
                else Text("Sign in")
            }
        } else {
            Text("Enter the 6-digit code from your authenticator.",
                style=MaterialTheme.typography.bodyMedium)
            OutlinedTextField(otp,{otp=it.filter(Char::isDigit).take(6)},
                label={Text("Authenticator code")},singleLine=true,shape=RectangleShape,
                keyboardOptions=KeyboardOptions(keyboardType=KeyboardType.NumberPassword),
                modifier=Modifier.fillMaxWidth())
            Button(onClick={vm.verifyOtp(otp)},shape=RectangleShape,
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
private fun ChoiceField(label:String,items:List<Choice>,value:Int,onChoose:(Int)->Unit) {
    var expanded by remember { mutableStateOf(false) }
    Column {
        Text(label,style=MaterialTheme.typography.labelMedium)
        Box {
            OutlinedButton(onClick={expanded=true},modifier=Modifier.fillMaxWidth()){
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
    var type by remember { mutableStateOf("public_project") }
    var lga by remember { mutableIntStateOf(0) }
    var sector by remember { mutableIntStateOf(0) }
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

    Content("New field report",
        "Saved reports are encrypted and queued when there is no network.") {
        if(vm.lgas.isEmpty())Text("No assigned LGA. Contact your RAWYOD Administrator.",
            color=MaterialTheme.colorScheme.error)
        Text("Report category",fontWeight=FontWeight.SemiBold)
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            FilterChip(selected=type=="public_project",onClick={type="public_project"},
                label={Text("Public project")})
            FilterChip(selected=type=="governance_issue",onClick={type="governance_issue"},
                label={Text("Governance issue")})
        }
        ChoiceField("Assigned LGA",vm.lgas,lga){lga=it}
        ChoiceField("Sector",vm.sectors,sector){sector=it}
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
        OutlinedButton(onClick={
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
            OutlinedButton(onClick={
                val target=File(context.cacheDir,"camera").also {it.mkdirs()}
                val file=File(target,"field-${UUID.randomUUID()}.jpg")
                cameraFile=file
                val uri=FileProvider.getUriForFile(context,
                    context.packageName+".fileprovider",file)
                camera.launch(uri)
            },enabled=vm.attachments.size<5 && !vm.busy,modifier=Modifier.weight(1f)){
                Text("Camera")
            }
            OutlinedButton(onClick={picker.launch(arrayOf(
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
        Button(modifier=Modifier.fillMaxWidth(),enabled=!vm.busy && vm.lgas.isNotEmpty(),
            onClick={
                try {
                    val json=JSONObject()
                        .put("type",type).put("title",title.trim())
                        .put("description",description.trim()).put("community",community.trim())
                        .put("lga_id",lga).put("sector_id",sector)
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
            Text(if(vm.busy)"Saving..." else "Encrypt and queue report")
        }
    }
}

@Composable
private fun OutboxScreen(vm:FieldViewModel) {
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
                    if(draft.status=="rejected" || draft.status=="retry") {
                        TextButton(onClick={vm.retryDraft(draft.id)}) {Text("Retry upload")}
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
    Content("My submitted reports","Review verification status and respond to MEAL clarification requests.") {
        OutlinedButton(onClick=vm::refreshReports,modifier=Modifier.fillMaxWidth()){
            Text("Refresh from server")
        }
        vm.reports.forEach{report->
            Surface(border=BorderStroke(1.dp,Color(0xFFCBD7CC))) {
                Column(Modifier.fillMaxWidth().clickable { vm.selectReport(report.id) }
                    .padding(12.dp)){
                    Text(report.title,fontWeight=FontWeight.Bold)
                    Text("${report.reference}  •  ${report.status.replace('_',' ')}",
                        style=MaterialTheme.typography.labelMedium)
                }
            }
        }
        vm.selectedReport?.let{detail->
            HorizontalDivider()
            Text(detail.title,style=MaterialTheme.typography.titleLarge)
            Text(detail.description)
            Text("Status: ${detail.status.replace('_',' ')}")
            detail.followups.forEach{Text("Follow-up: $it")}
            if(detail.status=="clarification_requested"){
                OutlinedTextField(response,{response=it},label={Text("Clarification response")},
                    modifier=Modifier.fillMaxWidth(),minLines=3)
                Button(onClick={
                    vm.respondToClarification(response);response=""
                },enabled=response.length>=5){Text("Submit clarification")}
            }
        }
        OutlinedButton(onClick=vm::logout,modifier=Modifier.fillMaxWidth()){
            Text("Sign out")
        }
    }
}
