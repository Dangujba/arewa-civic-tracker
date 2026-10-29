package ng.org.rawyod.arewa.ui

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import androidx.core.content.ContextCompat

object LocationOnce {
    @SuppressLint("MissingPermission")
    fun capture(context: Context, done: (Location?) -> Unit) {
        val fine=ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_FINE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
        val coarse=ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_COARSE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
        if(!fine && !coarse) {done(null);return}
        val manager=context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
        val provider=when {
            fine && manager.isProviderEnabled(LocationManager.GPS_PROVIDER) -> LocationManager.GPS_PROVIDER
            manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER) -> LocationManager.NETWORK_PROVIDER
            else -> {done(null);return}
        }
        if(Build.VERSION.SDK_INT>=Build.VERSION_CODES.R) {
            manager.getCurrentLocation(provider,null,ContextCompat.getMainExecutor(context)) { done(it) }
        } else {
            val listener=object:LocationListener {
                override fun onLocationChanged(location: Location) { done(location) }
                override fun onStatusChanged(p:String?, status:Int, extras:Bundle?) {}
                override fun onProviderEnabled(provider:String) {}
                override fun onProviderDisabled(provider:String) {done(null)}
            }
            manager.requestSingleUpdate(provider,listener,Looper.getMainLooper())
            Handler(Looper.getMainLooper()).postDelayed({manager.removeUpdates(listener)},18000)
        }
    }
}
