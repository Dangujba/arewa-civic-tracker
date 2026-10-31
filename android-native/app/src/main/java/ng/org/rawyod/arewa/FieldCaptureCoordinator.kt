package ng.org.rawyod.arewa

import android.Manifest
import android.app.Activity
import android.content.ClipData
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.provider.MediaStore
import android.provider.Settings
import android.util.Log
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import ng.org.rawyod.arewa.ui.FieldViewModel
import ng.org.rawyod.arewa.ui.LocationOnce
import java.io.File
import java.util.UUID

/**
 * Direct Android framework permission / intent flow.
 *
 * v0.3.7 used ActivityResultLauncher registrations whose launches failed on the
 * affected handset. This uses requestPermissions/startActivityForResult as a
 * compatibility path that avoids that registry entirely. Every result is routed
 * through MainActivity; no result depends on the conditional Compose form.
 *
 * Uses the native Documents app, with ACTION_GET_CONTENT fallback. The system
 * FileProvider URI allows camera apps to write into the private temp directory.
 */
@Suppress("DEPRECATION")
internal class FieldCaptureCoordinator(
    private val activity: MainActivity,
    private val vm: FieldViewModel,
    state: Bundle?
) {
    private var pendingCameraPath: String? = state?.getString(CAMERA_PATH)

    private fun granted(permission:String):Boolean =
        ContextCompat.checkSelfPermission(activity,permission)==PackageManager.PERMISSION_GRANTED

    private fun locationGranted():Boolean =
        granted(Manifest.permission.ACCESS_FINE_LOCATION) ||
        granted(Manifest.permission.ACCESS_COARSE_LOCATION)

    private fun failure(action:String,error:Throwable,description:String) {
        Log.e("ArewaCapture","$action failed: ${error.javaClass.simpleName}",error)
        vm.endExternalPicker()
        vm.captureFeedback("$description (${error.javaClass.simpleName}).")
    }

    fun requestGps() {
        if(vm.locating) {
            vm.captureFeedback("GPS search is already running.")
            return
        }
        if(locationGranted()) {
            captureLocation()
            return
        }
        vm.beginExternalPicker("Requesting location permission…")
        try {
            ActivityCompat.requestPermissions(activity,arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            ),REQ_GPS_PERMISSION)
        } catch(error:Exception) {
            failure("location permission",error,"Unable to request location access")
        }
    }

    private fun captureLocation() {
        vm.startLocation()
        LocationOnce.capture(activity) {location ->
            activity.runOnUiThread {
                vm.completeLocation(location?.latitude,location?.longitude)
            }
        }
    }

    fun requestCamera() {
        if(vm.busy) {
            vm.captureFeedback("Wait for evidence encryption to finish.")
            return
        }
        if(granted(Manifest.permission.CAMERA)) {
            openCamera()
            return
        }
        vm.beginExternalPicker("Requesting camera permission…")
        try {
            ActivityCompat.requestPermissions(activity,
                arrayOf(Manifest.permission.CAMERA),REQ_CAMERA_PERMISSION)
        }catch(error:Exception) {
            failure("camera permission",error,"Unable to request camera access")
        }
    }

    private fun openCamera() {
        var photo:File?=null
        try {
            val directory=File(activity.cacheDir,"camera")
            check(directory.isDirectory || directory.mkdirs()) {"Cannot create camera directory"}
            photo=File(directory,"field-${UUID.randomUUID()}.jpg")
            check(photo.createNewFile()) {"Cannot prepare camera output"}
            val uri=FileProvider.getUriForFile(activity,
                activity.packageName+".fileprovider",photo)
            val intent=Intent(MediaStore.ACTION_IMAGE_CAPTURE).apply {
                putExtra(MediaStore.EXTRA_OUTPUT,uri)
                clipData=ClipData.newUri(activity.contentResolver,"Field photograph",uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or
                    Intent.FLAG_GRANT_WRITE_URI_PERMISSION)
            }
            pendingCameraPath=photo.absolutePath
            vm.beginExternalPicker("Opening device camera…")
            activity.startActivityForResult(intent,REQ_CAMERA_RESULT)
        }catch(error:Exception) {
            photo?.delete()
            pendingCameraPath=null
            failure("camera",error,"Could not open a camera application")
        }
    }

    fun requestFiles() {
        if(vm.busy) {
            vm.captureFeedback("Wait for the current evidence file to finish encrypting.")
            return
        }
        val documents=Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type="*/*"
            putExtra(Intent.EXTRA_MIME_TYPES,arrayOf(
                "image/jpeg","image/png","image/webp","application/pdf"
            ))
            putExtra(Intent.EXTRA_ALLOW_MULTIPLE,true)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or
                Intent.FLAG_GRANT_PERSISTABLE_URI_PERMISSION)
        }
        vm.beginExternalPicker("Opening Android file picker…")
        try {
            activity.startActivityForResult(documents,REQ_FILE_RESULT)
        } catch(first:Exception) {
            Log.w("ArewaCapture","Document picker unavailable, trying file-content picker",first)
            val content=Intent(Intent.ACTION_GET_CONTENT).apply {
                addCategory(Intent.CATEGORY_OPENABLE)
                type="*/*"
                putExtra(Intent.EXTRA_MIME_TYPES,arrayOf(
                    "image/jpeg","image/png","image/webp","application/pdf"
                ))
                putExtra(Intent.EXTRA_ALLOW_MULTIPLE,true)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            try {
                activity.startActivityForResult(
                    Intent.createChooser(content,"Select photographic or PDF evidence"),
                    REQ_FILE_RESULT
                )
            }catch(second:Exception) {
                failure("evidence picker",second,"No compatible file picker is available")
            }
        }
    }

    fun onPermissionResult(requestCode:Int,permissions:Array<out String>,
        grantResults:IntArray):Boolean {
        when(requestCode) {
            REQ_GPS_PERMISSION -> {
                vm.endExternalPicker()
                if(locationGranted())captureLocation()
                else {
                    vm.captureFeedback("Location permission denied. Enable location in app settings or enter GPS manually.")
                }
                return true
            }
            REQ_CAMERA_PERMISSION -> {
                vm.endExternalPicker()
                if(granted(Manifest.permission.CAMERA))openCamera()
                else {
                    vm.captureFeedback("Camera permission denied. Enable Camera in app settings.")
                }
                return true
            }
        }
        return false
    }

    fun onActivityResult(requestCode:Int,resultCode:Int,data:Intent?):Boolean {
        when(requestCode){
            REQ_CAMERA_RESULT -> {
                vm.endExternalPicker()
                val image=pendingCameraPath?.let(::File)
                pendingCameraPath=null
                // Certain OEM camera apps return RESULT_CANCELED even after
                // writing the photograph. The actual file is authoritative.
                if(image?.isFile==true && image.length()>0L){
                    vm.captureFeedback("Camera photo captured. Encrypting…")
                    vm.captureEvidence(Uri.fromFile(image),image.name,"image/jpeg"){
                        image.delete()
                    }
                } else {
                    image?.delete()
                    vm.captureFeedback("No photograph captured. You can try Camera again.")
                }
                return true
            }
            REQ_FILE_RESULT -> {
                vm.endExternalPicker()
                if(resultCode!=Activity.RESULT_OK) {
                    vm.captureFeedback("File selection cancelled.")
                    return true
                }
                val uris=mutableListOf<Uri>()
                data?.clipData?.let{clip->
                    for(i in 0 until clip.itemCount) {
                        clip.getItemAt(i).uri?.let(uris::add)
                    }
                }
                data?.data?.let {if(it !in uris)uris.add(it)}
                if(uris.isEmpty())vm.captureFeedback("No file was returned by the picker.")
                else vm.captureMany(uris.distinct().take(5))
                return true
            }
        }
        return false
    }

    fun openPermissions(){
        try {
            activity.startActivity(Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply{
                data=Uri.parse("package:${activity.packageName}")
            })
        }catch(error:Exception){
            failure("open app settings",error,"Please open Android Settings → Apps → Arewa Civic Tracker")
        }
    }

    fun saveState(out:Bundle){
        out.putString(CAMERA_PATH,pendingCameraPath)
    }

    private companion object {
        const val REQ_GPS_PERMISSION=7101
        const val REQ_CAMERA_PERMISSION=7102
        const val REQ_CAMERA_RESULT=7103
        const val REQ_FILE_RESULT=7104
        const val CAMERA_PATH="field_camera_path"
    }
}
