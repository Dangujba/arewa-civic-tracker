package ng.org.rawyod.arewa.data

import android.content.res.AssetManager
import org.json.JSONObject
import java.util.Locale

/**
 * Offline Yobe State ward reference. Bundled with the APK and independent of
 * the signed-in server catalog. A stale bootstrap never removes the selector.
 */
object WardCatalog {
    fun normalizeLga(name:String):String =
        name.lowercase(Locale.ROOT).filter {it.isLetterOrDigit()}

    fun readNames(json:String,lgaName:String):List<String> {
        if(lgaName.isBlank())return emptyList()
        val target=normalizeLga(lgaName)
        val lgas=JSONObject(json).getJSONArray("lgas")
        for(i in 0 until lgas.length()){
            val item=lgas.getJSONObject(i)
            if(normalizeLga(item.getString("lga"))!=target)continue
            val names=item.getJSONArray("wards")
            return (0 until names.length()).map{
                names.getJSONObject(it).getString("name").trim()
            }.filter(String::isNotBlank).distinct()
        }
        return emptyList()
    }

    fun fromAssets(assets:AssetManager):Map<String,List<String>> {
        val json=assets.open("yobe_wards.json").bufferedReader().use{it.readText()}
        val result=linkedMapOf<String,List<String>>()
        val items=JSONObject(json).getJSONArray("lgas")
        for(i in 0 until items.length()){
            val item=items.getJSONObject(i)
            result[normalizeLga(item.getString("lga"))]=readNames(json,item.getString("lga"))
        }
        return result
    }
}
