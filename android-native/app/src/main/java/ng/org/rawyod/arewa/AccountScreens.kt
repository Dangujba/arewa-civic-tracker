package ng.org.rawyod.arewa

import androidx.compose.foundation.*
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.RectangleShape
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import ng.org.rawyod.arewa.ui.FieldViewModel

private val brandGreen=Color(0xFF17543B)
private val border=Color(0xFFD6E3DA)

@Composable
private fun ScreenSection(title:String,icon:ImageVector,content:@Composable ColumnScope.()->Unit){
    Column(Modifier.fillMaxWidth().border(1.dp,border).background(Color.White).padding(16.dp),
        verticalArrangement=Arrangement.spacedBy(12.dp)){
        Row(horizontalArrangement=Arrangement.spacedBy(10.dp),verticalAlignment=Alignment.CenterVertically){
            Icon(icon,contentDescription=null,tint=brandGreen,modifier=Modifier.size(20.dp))
            Text(title,fontWeight=FontWeight.Bold,style=MaterialTheme.typography.titleMedium)
        }
        content()
    }
}

@Composable
private fun SecretField(label:String,value:String,onChange:(String)->Unit,number:Boolean=false){
    OutlinedTextField(value,onChange,label={Text(label)},
        visualTransformation=PasswordVisualTransformation(),
        keyboardOptions=KeyboardOptions(keyboardType=if(number) KeyboardType.NumberPassword else KeyboardType.Password),
        singleLine=true,shape=RectangleShape,modifier=Modifier.fillMaxWidth())
}

@Composable
private fun ScreenBody(title:String,content:@Composable ColumnScope.()->Unit){
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())
        .padding(horizontal=16.dp,vertical=18.dp),
        verticalArrangement=Arrangement.spacedBy(14.dp)){
        Text(title,style=MaterialTheme.typography.headlineSmall,fontWeight=FontWeight.Bold)
        content()
        Spacer(Modifier.height(22.dp))
    }
}

@Composable
internal fun ProfileScreen(vm:FieldViewModel){
    val info=vm.account
    ScreenBody("My profile"){
        ScreenSection("Profile",Icons.Filled.AccountCircle){
            Row(horizontalArrangement=Arrangement.spacedBy(12.dp),verticalAlignment=Alignment.CenterVertically){
                Icon(Icons.Filled.AccountCircle,contentDescription=null,tint=brandGreen,
                    modifier=Modifier.size(52.dp))
                Column {
                    Text(info.optString("name").ifBlank{vm.userName},
                        fontWeight=FontWeight.Bold,style=MaterialTheme.typography.titleMedium)
                    Text(info.optString("email").ifBlank{"Email available after sign-in"},
                        style=MaterialTheme.typography.bodySmall,color=Color.DarkGray)
                }
            }
            HorizontalDivider()
            Text("Role: Civic Intelligence Focal Person")
            Text("Account ID: ${vm.userId ?: "-"}",style=MaterialTheme.typography.bodySmall)
        }
        ScreenSection("Device access",Icons.Filled.Smartphone){
            Row(verticalAlignment=Alignment.CenterVertically){
                Icon(if(vm.offlinePinEnabled)Icons.Filled.CheckCircle else Icons.Filled.Info,
                    contentDescription=null,tint=brandGreen)
                Spacer(Modifier.width(8.dp))
                Text(if(vm.offlinePinEnabled)"Offline PIN available" else "Offline PIN not configured")
            }
            Text("Your account details and previously loaded reports are stored on this device.",
                style=MaterialTheme.typography.bodySmall)
        }
        OutlinedButton(shape=RectangleShape,onClick=vm::refreshProfile,
            enabled=!vm.refreshingCatalog,modifier=Modifier.fillMaxWidth()){
            if(vm.refreshingCatalog)CircularProgressIndicator(Modifier.size(18.dp),strokeWidth=2.dp)
            else Icon(Icons.Filled.Refresh,contentDescription=null)
            Spacer(Modifier.width(8.dp))
            Text(if(vm.refreshingCatalog)"Refreshing…" else "Refresh profile")
        }
    }
}

@Composable
internal fun SecuritySettingsScreen(
    vm:FieldViewModel,
    available:Boolean,
    notice:String,
    authenticate:(String,()->Unit)->Unit
) {
    var pin by remember {mutableStateOf("")}
    var pinConfirm by remember {mutableStateOf("")}
    var currentPin by remember {mutableStateOf("")}
    var currentPassword by remember {mutableStateOf("")}
    var nextPassword by remember {mutableStateOf("")}
    var confirmedPassword by remember {mutableStateOf("")}
    var factorPassword by remember {mutableStateOf("")}
    var factorCode by remember {mutableStateOf("")}
    var passwordOpen by remember {mutableStateOf(false)}
    var factorOpen by remember {mutableStateOf(false)}
    var signoutDialog by remember {mutableStateOf(false)}
    ScreenBody("Settings"){
        ScreenSection("Security",Icons.Filled.Security){
            Row(Modifier.fillMaxWidth(),horizontalArrangement=Arrangement.SpaceBetween,
                verticalAlignment=Alignment.CenterVertically){
                Column {
                    Text("Fingerprint lock",fontWeight=FontWeight.Medium)
                    Text(if(vm.fingerprintEnabled)"Enabled" else "Disabled",
                        style=MaterialTheme.typography.bodySmall,color=Color.DarkGray)
                }
                if(available){
                    Switch(checked=vm.fingerprintEnabled,onCheckedChange={enabled->
                        authenticate(if(enabled)"Enable fingerprint" else "Disable fingerprint"){
                            vm.changeFingerprintSetting(enabled)
                        }
                    })
                }else if(vm.fingerprintEnabled) {
                    TextButton(onClick={vm.changeFingerprintSetting(false)}){Text("Remove")}
                }
            }
            if(!available)Text("Fingerprint not available on this device.",
                style=MaterialTheme.typography.bodySmall)
            if(vm.fingerprintEnabled)TextButton(onClick=vm::lockOnBackground){Text("Lock now")}
            HorizontalDivider()
            Text("Offline PIN",fontWeight=FontWeight.Medium)
            if(vm.offlinePinEnabled){
                SecretField("Current PIN",currentPin,{currentPin=it.filter(Char::isDigit).take(12)},true)
                OutlinedButton(shape=RectangleShape,enabled=currentPin.length>=6,
                    onClick={vm.removeOfflinePin(currentPin);currentPin=""},
                    modifier=Modifier.fillMaxWidth()){Text("Remove offline PIN")}
            }else{
                SecretField("Choose 6 to 12 digit PIN",pin,{pin=it.filter(Char::isDigit).take(12)},true)
                SecretField("Confirm PIN",pinConfirm,{pinConfirm=it.filter(Char::isDigit).take(12)},true)
                Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                    enabled=pin.length>=6 && !vm.busy,
                    onClick={vm.enableOfflinePin(pin,pinConfirm);pin="";pinConfirm=""}){
                    Text("Enable offline access")
                }
            }
        }
        ScreenSection("Password",Icons.Filled.Lock){
            TextButton(onClick={passwordOpen=!passwordOpen}){
                Text(if(passwordOpen)"Close password settings" else "Change password")
            }
            if(passwordOpen){
                SecretField("Current password",currentPassword,{currentPassword=it})
                SecretField("New password",nextPassword,{nextPassword=it})
                SecretField("Confirm new password",confirmedPassword,{confirmedPassword=it})
                Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                    enabled=!vm.busy && currentPassword.isNotBlank() && nextPassword.length>=10,
                    onClick={
                        vm.changePassword(currentPassword,nextPassword,confirmedPassword)
                        currentPassword="";nextPassword="";confirmedPassword=""
                    }) {
                    if(vm.busy)CircularProgressIndicator(Modifier.size(18.dp),strokeWidth=2.dp)
                    else Text("Save password")
                }
            }
        }
        ScreenSection("Two-factor authentication",Icons.Filled.VerifiedUser){
            Text(if(vm.account.optBoolean("two_factor_enabled"))"Enabled" else "Disabled",
                color=brandGreen)
            TextButton(onClick={factorOpen=!factorOpen}){Text(if(factorOpen)"Close 2FA settings" else "Manage 2FA")}
            if(factorOpen){
                if(vm.twoFactorSecret.isNotBlank()){
                    Text("Authenticator setup key",fontWeight=FontWeight.Bold)
                    Text(vm.twoFactorSecret)
                    Text("Enter this key into your authenticator app.",
                        style=MaterialTheme.typography.bodySmall)
                }
                if(vm.account.optBoolean("two_factor_enabled")){
                    SecretField("Account password",factorPassword,{factorPassword=it})
                    SecretField("Authenticator code",factorCode,
                        {factorCode=it.filter(Char::isDigit).take(6)},true)
                    OutlinedButton(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                        enabled=!vm.busy && factorPassword.isNotBlank() && factorCode.length==6,
                        onClick={vm.disableTwoFactor(factorPassword,factorCode);
                            factorPassword="";factorCode=""}){Text("Disable 2FA")}
                }else if(vm.twoFactorSecret.isNotBlank()){
                    SecretField("6-digit authenticator code",factorCode,
                        {factorCode=it.filter(Char::isDigit).take(6)},true)
                    Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                        enabled=!vm.busy && factorCode.length==6,
                        onClick={vm.confirmTwoFactor(factorCode);factorCode=""}){Text("Verify and enable 2FA")}
                }else{
                    SecretField("Current account password",factorPassword,{factorPassword=it})
                    Button(shape=RectangleShape,modifier=Modifier.fillMaxWidth(),
                        enabled=!vm.busy && factorPassword.isNotBlank(),
                        onClick={vm.beginTwoFactor(factorPassword);factorPassword=""}){Text("Set up 2FA")}
                }
                if(vm.recoveryCodes.isNotEmpty()){
                    HorizontalDivider()
                    Text("Save these one-time recovery codes securely:",fontWeight=FontWeight.Bold)
                    vm.recoveryCodes.forEach{Text(it)}
                }
            }
        }
        ScreenSection("Account",Icons.Filled.Logout){
            OutlinedButton(shape=RectangleShape,onClick={signoutDialog=true},
                modifier=Modifier.fillMaxWidth()){
                Icon(Icons.Filled.Logout,contentDescription=null)
                Spacer(Modifier.width(8.dp));Text("Sign out")
            }
        }
        if(notice.isNotBlank())Text(notice,color=MaterialTheme.colorScheme.error)
    }
    if(signoutDialog)AlertDialog(onDismissRequest={signoutDialog=false},
        title={Text("Sign out?")},
        text={Text("Any unsynchronized reports must be uploaded before sign-out. Your saved account will be removed from this device.")},
        confirmButton={TextButton(onClick={signoutDialog=false;vm.logout()}){Text("Sign out")}},
        dismissButton={TextButton(onClick={signoutDialog=false}){Text("Cancel")}})
}
