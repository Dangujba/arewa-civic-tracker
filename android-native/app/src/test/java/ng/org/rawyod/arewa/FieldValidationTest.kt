package ng.org.rawyod.arewa

import ng.org.rawyod.arewa.ui.validatePayload
import org.json.JSONObject
import org.junit.Assert.*
import org.junit.Test

class FieldValidationTest {
    private fun valid(): JSONObject = JSONObject().put("type", "public_project")
        .put("title","Health center inspection").put("description","Documented conditions observed in the field")
        .put("community","Damaturu").put("lga_id",1).put("sector_id",2)
        .put("observation_date","2026-10-29")

    @Test fun acceptsValidReport() {
        validatePayload(valid())
    }

    @Test fun rejectsInvalidTitle() {
        assertThrows(IllegalArgumentException::class.java) { validatePayload(valid().put("title","a")) }
    }

    @Test fun rejectsInvalidAssignedArea() {
        assertThrows(IllegalArgumentException::class.java) { validatePayload(valid().put("lga_id",0)) }
    }

    @Test fun rejectsInvalidDate() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("observation_date","29/10/2026"))
        }
    }
}
