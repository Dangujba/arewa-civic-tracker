package ng.org.rawyod.arewa

import ng.org.rawyod.arewa.ui.validatePayload
import org.json.JSONObject
import org.junit.Assert.*
import org.junit.Test

class FieldValidationTest {
    private fun valid(): JSONObject = JSONObject().put("type", "public_project")
        .put("title","Health center inspection").put("description","Documented conditions observed in the field")
        .put("community","Damaturu").put("lga_id",1).put("sector_id",2)
        .put("observation_date","2026-10-29").put("progress_status","ongoing")

    @Test fun acceptsValidReport() {
        validatePayload(valid())
    }

    @Test fun rejectsInvalidTitle() {
        assertThrows(IllegalArgumentException::class.java) { validatePayload(valid().put("title","a")) }
    }

    @Test fun rejectsInvalidAssignedArea() {
        assertThrows(IllegalArgumentException::class.java) { validatePayload(valid().put("lga_id",0)) }
    }

    @Test fun rejectsMissingCoordinatesPair() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("latitude",11.4))
        }
    }
    @Test fun rejectsBadCoordinateRange() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("latitude",200).put("longitude",12.1))
        }
    }
    @Test fun rejectsFutureObservationDate() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("observation_date","2999-12-01"))
        }
    }
    @Test fun rejectsUnknownProgressStatus() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("progress_status","unknown"))
        }
    }
    @Test fun rejectsInvalidDate() {
        assertThrows(IllegalArgumentException::class.java) {
            validatePayload(valid().put("observation_date","29/10/2026"))
        }
    }
}
