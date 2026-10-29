package ng.org.rawyod.arewa.data

import android.content.Context
import androidx.room.Dao
import androidx.room.Database
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.PrimaryKey
import androidx.room.Query
import androidx.room.Room
import androidx.room.RoomDatabase
import kotlinx.coroutines.flow.Flow

@Entity(tableName="drafts", indices=[Index("ownerId")])
data class Draft(
    @PrimaryKey val id: String,
    val ownerId: Long,
    val encryptedPayload: ByteArray,
    val savedAt: Long,
    val status: String = "pending",
    val lastError: String? = null
)

@Entity(tableName="evidence",
    foreignKeys=[ForeignKey(entity=Draft::class,
        parentColumns=["id"], childColumns=["draftId"], onDelete=ForeignKey.CASCADE)],
    indices=[Index("draftId")])
data class Evidence(
    @PrimaryKey val id: String,
    val draftId: String,
    val encryptedPath: String,
    val name: String,
    val mimeType: String,
    val originalSize: Long
)

@Dao
interface FieldDao {
    @Insert(onConflict=OnConflictStrategy.REPLACE)
    suspend fun putDraft(draft: Draft)
    @Insert(onConflict=OnConflictStrategy.REPLACE)
    suspend fun putEvidence(evidence: Evidence)
    @Query("SELECT * FROM drafts WHERE ownerId=:owner ORDER BY savedAt DESC")
    fun observeDrafts(owner: Long): Flow<List<Draft>>
    @Query("SELECT * FROM drafts WHERE ownerId=:owner AND status IN ('pending','retry') ORDER BY savedAt ASC")
    suspend fun unsent(owner: Long): List<Draft>
    @Query("SELECT * FROM drafts WHERE id=:id AND ownerId=:owner LIMIT 1")
    suspend fun draft(id: String, owner: Long): Draft?
    @Query("SELECT * FROM evidence WHERE draftId=:id")
    suspend fun evidenceFor(id: String): List<Evidence>
    @Query("UPDATE drafts SET status=:state,lastError=:error WHERE id=:id AND ownerId=:owner")
    suspend fun mark(id: String, owner: Long, state: String, error: String?)
    @Query("DELETE FROM drafts WHERE id=:id AND ownerId=:owner")
    suspend fun deleteDraft(id: String, owner: Long)
    @Query("SELECT COUNT(*) FROM drafts WHERE ownerId=:owner")
    suspend fun count(owner: Long): Int
}

@Database(entities=[Draft::class,Evidence::class], version=1, exportSchema=false)
abstract class FieldDatabase : RoomDatabase() {
    abstract fun dao(): FieldDao
    companion object {
        @Volatile private var instance: FieldDatabase? = null
        fun open(context: Context): FieldDatabase = instance ?: synchronized(this) {
            instance ?: Room.databaseBuilder(
                context.applicationContext, FieldDatabase::class.java, "field_outbox.db"
            ).build().also { instance = it }
        }
    }
}
