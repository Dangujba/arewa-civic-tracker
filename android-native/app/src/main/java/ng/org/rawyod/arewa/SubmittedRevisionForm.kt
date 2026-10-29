package ng.org.rawyod.arewa

import androidx.compose.foundation.layout.*
import androidx.compose.ui.graphics.RectangleShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import ng.org.rawyod.arewa.ui.*
import org.json.JSONObject

@Composable
internal fun SubmittedRevisionForm(
    vm:FieldViewModel,detail:ReportDetails,
    onCancel:()->Unit,onSaved:()->Unit
){
    val data=detail.fields
    var type by remember(detail.id,detail.version){mutableStateOf(data.optString("type","public_project"))}
    var lga by remember(detail.id,detail.version){mutableIntStateOf(data.optInt("lga_id"))}
    var sector by remember(detail.id,detail.version){mutableIntStateOf(data.optInt("sector_id"))}
    var category by remember(detail.id,detail.version){mutableIntStateOf(data.optInt("report_category_id"))}
    var title by remember(detail.id,detail.version){mutableStateOf(data.optString("title"))}
    var description by remember(detail.id,detail.version){mutableStateOf(data.optString("description"))}
    var community by remember(detail.id,detail.version){mutableStateOf(data.optString("community"))}
    var date by remember(detail.id,detail.version){mutableStateOf(data.optString("observation_date"))}
    var observations by remember(detail.id,detail.version){mutableStateOf(data.optString("observations",""))}
    var dutyBearers by remember(detail.id,detail.version){mutableStateOf(data.optString("duty_bearers",""))}
    var latitude by remember(detail.id,detail.version){mutableStateOf(if(data.isNull("latitude"))"" else data.optString("latitude"))}
    var longitude by remember(detail.id,detail.version){mutableStateOf(if(data.isNull("longitude"))"" else data.optString("longitude"))}
    var reason by remember(detail.id,detail.version){mutableStateOf("")}
    Column(verticalArrangement=Arrangement.spacedBy(12.dp)){
        Text("Edit Submitted Report",style=MaterialTheme.typography.titleLarge,fontWeight=FontWeight.Bold)
        Text("Revision "+detail.version+" · An edit reason is required.",style=MaterialTheme.typography.labelMedium)
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            FilterChip(selected=type=="public_project",onClick={type="public_project"},
                shape=RectangleShape,label={Text("Public Project")})
            FilterChip(selected=type=="governance_issue",onClick={type="governance_issue"},
                shape=RectangleShape,label={Text("Governance Issue")})
        }
        ChoiceField("Assigned LGA",vm.lgas,lga){lga=it}
        ChoiceField("Sector",vm.sectors,sector){sector=it}
        val categories=vm.categories.filter{it.type==type}
        if(categories.isNotEmpty())ChoiceField("Report Category",
            listOf(Choice(0,"None"))+categories.map{Choice(it.id,it.name)},
            if(categories.any{it.id==category})category else 0){category=it}
        OutlinedTextField(title,{title=it},label={Text("Report Title")},shape=RectangleShape,modifier=Modifier.fillMaxWidth())
        OutlinedTextField(description,{description=it},label={Text("Description")},shape=RectangleShape,minLines=3,modifier=Modifier.fillMaxWidth())
        OutlinedTextField(community,{community=it},label={Text("Community")},shape=RectangleShape,modifier=Modifier.fillMaxWidth())
        OutlinedTextField(date,{date=it},label={Text("Observation Date (YYYY-MM-DD)")},shape=RectangleShape,modifier=Modifier.fillMaxWidth())
        OutlinedTextField(observations,{observations=it},label={Text("Observations")},shape=RectangleShape,minLines=2,modifier=Modifier.fillMaxWidth())
        OutlinedTextField(dutyBearers,{dutyBearers=it},label={Text("Duty Bearers")},shape=RectangleShape,minLines=2,modifier=Modifier.fillMaxWidth())
        Row(horizontalArrangement=Arrangement.spacedBy(8.dp)){
            OutlinedTextField(latitude,{latitude=it},label={Text("Latitude")},shape=RectangleShape,modifier=Modifier.weight(1f))
            OutlinedTextField(longitude,{longitude=it},label={Text("Longitude")},shape=RectangleShape,modifier=Modifier.weight(1f))
        }
        OutlinedTextField(reason,{reason=it},label={Text("Reason for Revision")},shape=RectangleShape,minLines=2,modifier=Modifier.fillMaxWidth())
        Button(shape=RectangleShape,enabled=!vm.busy&&reason.trim().length>=5,
            modifier=Modifier.fillMaxWidth(),onClick={
                runCatching{
                    val payload=JSONObject()
                        .put("type",type).put("title",title.trim())
                        .put("description",description.trim()).put("lga_id",lga)
                        .put("community",community.trim()).put("sector_id",sector)
                        .put("report_category_id",if(category>0&&vm.categories.any{it.id==category&&it.type==type})category else JSONObject.NULL)
                        .put("observation_date",date.trim()).put("observations",observations)
                        .put("duty_bearers",dutyBearers)
                        .put("progress_status",data.optString("progress_status","ongoing"))
                        .put("reason",reason.trim())
                    if(latitude.isNotBlank()||longitude.isNotBlank()){
                        payload.put("latitude",latitude.toDouble())
                        payload.put("longitude",longitude.toDouble())
                    }
                    vm.reviseSubmittedReport(detail,payload,onSaved)
                }.onFailure{vm.display(it.message?:"Please correct the report fields.")}
            }){
            if(vm.busy)CircularProgressIndicator(Modifier.size(18.dp),strokeWidth=2.dp,color=Color.White)
            Spacer(Modifier.width(6.dp))
            Text("Save Revision")
        }
        OutlinedButton(shape=RectangleShape,onClick=onCancel,enabled=!vm.busy,modifier=Modifier.fillMaxWidth()){Text("Cancel")}
    }
}
