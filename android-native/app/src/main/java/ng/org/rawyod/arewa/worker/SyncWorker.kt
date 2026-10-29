package ng.org.rawyod.arewa.worker

import android.content.Context
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkerParameters
import androidx.work.WorkManager
import ng.org.rawyod.arewa.ArewaApp
import ng.org.rawyod.arewa.network.ApiFailure
import java.io.IOException
import java.util.concurrent.TimeUnit

object SyncScheduler {
    fun enqueue(context: Context) {
        val job = OneTimeWorkRequestBuilder<SyncWorker>()
            .setConstraints(Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED).build())
            .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
            .build()
        WorkManager.getInstance(context).enqueueUniqueWork(
            "arewa-native-offline-sync", ExistingWorkPolicy.KEEP, job)
    }
}

class SyncWorker(context: Context, params: WorkerParameters) : CoroutineWorker(context, params) {
    override suspend fun doWork(): Result {
        val app = applicationContext as ArewaApp
        val session = app.store
        val owner = session.userId() ?: return Result.success()
        val token = session.token() ?: return Result.success()
        val dao = app.database.dao()
        for (draft in dao.unsent(owner)) {
            if (isStopped) return Result.retry()
            try {
                val payload = app.repository.decode(draft)
                app.api.upload(token, payload, dao.evidenceFor(draft.id))
                app.repository.deleteSent(draft)
            } catch (ex: ApiFailure) {
                val error = ex.message?.take(400) ?: "Upload failed"
                when {
                    ex.code == 401 || ex.code == 403 -> {
                        dao.mark(draft.id, owner, "retry", "Sign in again or check LGA assignment: $error")
                        return Result.success() // don't hammer the API when credentials are invalid
                    }
                    ex.code == 408 || ex.code == 429 || ex.code >= 500 -> {
                        dao.mark(draft.id, owner, "retry", error)
                        return Result.retry()
                    }
                    else -> dao.mark(draft.id, owner, "rejected", error)
                }
            } catch (ex: IOException) {
                dao.mark(draft.id, owner, "retry", ex.message?.take(400))
                return Result.retry()
            } catch (ex: Exception) {
                dao.mark(draft.id, owner, "rejected", ex.message?.take(400))
            }
        }
        return Result.success()
    }
}
