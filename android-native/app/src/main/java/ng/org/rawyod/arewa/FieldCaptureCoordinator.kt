package ng.org.rawyod.arewa

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.provider.Settings
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import ng.org.rawyod.arewa.ui.FieldViewModel
import ng.org.rawyod.arewa.ui.LocationOnce
import java.io.File
import java.util.UUID

/**
 * Register launchers with the Activity, not a conditional Compose screen.
 *
 * Activity-level launchers remain registered through a biometric lock, tab change,
 * or recomposition while Android's camera, permission UI, or file browser is open.
 * Evidence processing stays in FieldViewModel and its encrypted repository.
 */
internal class FieldCaptureCoordinator(
    private val activity: MainActivity,
    private val vm: FieldViewModel,
    state: Bundle?
) {
    private var pendingCameraPath: String? = state?.getString(CAMERA_PATH)

    private val takePicture=activity.registerForActivityResult(
        ActivityResultContracts.TakePicture()
    ) { saved ->
        vm.endExternalPicker()
        val photo=pendingCameraPath?.let(::File)
        pendingCameraPath=null
        if(saved && photo?.isFile==true && photo.length()>0L) {
            vm.captureFeedback("Photo captured. Encrypting evidence…")
            vm.captureEvidence(Uri.fromFile(photo),photo.name,"image/jpeg") {
                photo.delete()
            }
        } else {
            photo?.delete()
            vm.captureFeedback(if(saved) "The camera returned an empty photo. Try again."
                else "Camera closed without taking a photo.")
        }
    }

    private val chooseFiles=activity.registerForActivityResult(
        ActivityResultContracts.OpenMultipleDocuments()
    ) { uris ->
        vm.endExternalPicker()
        if(uris.isEmpty())vm.captureFeedback("No files selected.")
        else vm.captureMany(uris)
    }

    private val locationPermissions=activity.registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { grants ->
        vm.endExternalPicker()
        val permitted=grants[Manifest.permission.ACCESS_FINE_LOCATION]==true ||
            grants[Manifest.permission.ACCESS_COARSE_LOCATION]==true ||
            locationGranted()
        if(permitted)captureLocation()
        else vm.captureFeedback(
            "Location permission denied. Open Android App Info → Permissions → Location to allow access."
        )
    }

    private val cameraPermission=activity.registerForActivityResult(
        ActivityResultContracts.RequestPermission()
    ){permitted->
        vm.endExternalPicker()
        if(permitted || cameraGranted())startExternalCamera()
        else vm.captureFeedback(
            "Camera permission denied. Open Android App Info → Permissions → Camera to allow access."
        )
    }

    private fun cameraGranted() =
        ContextCompat.checkSelfPermission(activity,Manifest.permission.CAMERA)==
            PackageManager.PERMISSION_GRANTED

    private fun locationGranted() =
        ContextCompat.checkSelfPermission(activity,Manifest.permission.ACCESS_FINE_LOCATION)==
            PackageManager.PERMISSION_GRANTED ||
        ContextCompat.checkSelfPermission(activity,Manifest.permission.ACCESS_COARSE_LOCATION)==
            PackageManager.PERMISSION_GRANTED

    fun requestGps() {
        if(vm.locating){vm.captureFeedback("Already searching for GPS. Check the location status.");return}
        if(locationGranted())captureLocation()
        else {
            vm.beginExternalPicker("Requesting location permission…")
            runCatching {
                locationPermissions.launch(arrayOf(
                    Manifest.permission.ACCESS_FINE_LOCATION,Manifest.permission.ACCESS_COARSE_LOCATION
                ))
            }.onFailure {
                vm.endExternalPicker()
                vm.captureFeedback("Cannot request Location permission. Check Android app permissions.")
            }
        }
    }

    private fun captureLocation(){
        vm.startLocation()
        LocationOnce.capture(activity){location->
            vm.completeLocation(location?.latitude,location?.longitude)
        }
    }

    fun requestCamera(){
        if(vm.busy){vm.captureFeedback("A file is still being encrypted.");return}
        if(cameraGranted())startExternalCamera()
        else {
            vm.beginExternalPicker("Requesting Camera permission…")
            runCatching{cameraPermission.launch(Manifest.permission.CAMERA)}
                .onFailure{
                    vm.endExternalPicker()
                    vm.captureFeedback("Camera permission could not be requested. Check Android settings.")
                }
        }
    }

    private fun startExternalCamera() {
        var photo:File?=null
        try {
            val folder=File(activity.cacheDir,"camera")
            check(folder.isDirectory || folder.mkdirs()){"Unable to create camera folder"}
            photo=File(folder,"field-${UUID.randomUUID()}.jpg")
            check(photo.createNewFile()){"Unable to create temporary camera photo"}
            val uri=FileProvider.getUriForFile(activity,
                activity.packageName+".fileprovider",photo)
            pendingCameraPath=photo.absolutePath
            vm.beginExternalPicker("Opening camera…")
            takePicture.launch(uri)
        }catch(_:Exception){
            vm.endExternalPicker()
            pendingCameraPath=null
            photo?.delete()
            vm.captureFeedback("Camera unavailable. Try Add evidence to select an existing photo.")
        }
    }

    fun requestFiles(){
        if(vm.busy){vm.captureFeedback("Wait for the current file to finish encrypting.");return}
        vm.beginExternalPicker("Opening file picker…")
        runCatching {
            chooseFiles.launch(arrayOf("image/jpeg","image/png","image/webp","application/pdf"))
        }.onFailure {
            vm.endExternalPicker()
            vm.captureFeedback("File picker unavailable. Check the device's Documents app.")
        }
    }

    fun openPermissions(){
        runCatching {
            activity.startActivity(Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS).apply{
                data=Uri.parse("package:${activity.packageName}")
            })
        }.onFailure{vm.captureFeedback("Open Android Settings and allow Camera or Location permissions.")}
    }

    fun saveState(out:Bundle){
        out.putString(CAMERA_PATH,pendingCameraPath)
    }

    private companion object {
        const val CAMERA_PATH="field_camera_path"
    }
}
