package ng.org.rawyod.arewa

import ng.org.rawyod.arewa.data.WardCatalog
import org.json.JSONObject
import org.junit.Assert.*
import org.junit.Test
import java.io.File

class WardCatalogTest {
    private fun localReference():String {
        val candidates=listOf(
            File("src/main/assets/yobe_wards.json"),
            File("app/src/main/assets/yobe_wards.json")
        )
        return candidates.firstOrNull {it.isFile}?.readText()
            ?: error("APK ward reference asset missing")
    }

    @Test fun bundledWardDataCoversEveryYobeLga() {
        val json=localReference()
        val lgas=JSONObject(json).getJSONArray("lgas")
        assertEquals(17,lgas.length())
        var total=0
        for(i in 0 until lgas.length()){
            val lga=lgas.getJSONObject(i).getString("lga")
            val wards=WardCatalog.readNames(json,lga)
            assertTrue("No wards for $lga",wards.isNotEmpty())
            assertEquals(wards.size,wards.distinct().size)
            total+=wards.size
        }
        assertEquals(178,total)
    }

    @Test fun matchesDisplayLgaCaseAndWardCount() {
        val json=localReference()
        assertEquals(11,WardCatalog.readNames(json,"DAMATURU").size)
        assertTrue(WardCatalog.readNames(json,"Damaturu")
            .contains("Damaturu Central"))
        assertEquals(10,WardCatalog.readNames(json,"Gujba").size)
        assertEquals("karasuwa",WardCatalog.normalizeLga("Kara Suwa"))
    }

    @Test fun noLgaSelectedOrUnknownLgaYieldsEmpty() {
        val json=localReference()
        assertTrue(WardCatalog.readNames(json,"").isEmpty())
        assertTrue(WardCatalog.readNames(json,"Unknown Place").isEmpty())
    }
}
