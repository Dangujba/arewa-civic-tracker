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
import android.os.CancellationSignal
import android.os.SystemClock
import androidx.core.content.ContextCompat
import java.util.concurrent.atomic.AtomicBoolean

object LocationOnce {
    /** Uses device GPS without mobile data. Never throws into a Compose launcher callback. */
    @SuppressLint("MissingPermission")
    fun capture(context:Context, done:(Location?)->Unit) {
        val called=AtomicBoolean(false)
        val finish:(Location?)->Unit={found->if(called.compareAndSet(false,true))done(found)}
        try {
            val fine=ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_FINE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
            val coarse=ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_COARSE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
            if(!fine && !coarse) {finish(null);return}
            val manager=context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager
                ?: run{finish(null);return}
            val provider=when {
                fine && manager.isProviderEnabled(LocationManager.GPS_PROVIDER)->LocationManager.GPS_PROVIDER
                manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)->LocationManager.NETWORK_PROVIDER
                else->{finish(null);return}
            }
            val handler=Handler(Looper.getMainLooper())
            if(Build.VERSION.SDK_INT>=Build.VERSION_CODES.R){
                val cancellation=CancellationSignal()
                handler.postDelayed({cancellation.cancel();finish(null)},17000)
                manager.getCurrentLocation(provider,cancellation,
                    ContextCompat.getMainExecutor(context)){location->finish(location)}
            } else {
                val listener=object:LocationListener {
                    override fun onLocationChanged(location:Location){finish(location)}
                    override fun onStatusChanged(p:String?, status:Int, extras:Bundle?) {}
                    override fun onProviderEnabled(provider:String) {}
                    override fun onProviderDisabled(provider:String){finish(null)}
                }
                manager.requestSingleUpdate(provider,listener,Looper.getMainLooper())
                handler.postDelayed({manager.removeUpdates(listener);finish(null)},17000)
            }
        } catch(_:SecurityException){finish(null)}
          catch(_:IllegalArgumentException){finish(null)}
          catch(_:Exception){finish(null)}
    }
}
