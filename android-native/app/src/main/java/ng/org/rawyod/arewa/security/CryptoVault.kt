package ng.org.rawyod.arewa.security

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.CipherInputStream
import javax.crypto.CipherOutputStream
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec
import java.io.File
import java.io.InputStream
import java.io.OutputStream

/**
 * AES-256-GCM with non-exportable Android Keystore key. Every encryption uses a new IV.
 * Ciphertext record/file format: 12-byte nonce followed by authenticated ciphertext.
 */
class CryptoVault {
    private val alias = "rawyod.arewa.field.aes.v1"
    private val key: SecretKey by lazy {
        val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (store.getKey(alias, null) as? SecretKey) ?: KeyGenerator.getInstance(
            KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore"
        ).apply {
            init(KeyGenParameterSpec.Builder(
                alias, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
            ).setKeySize(256)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setRandomizedEncryptionRequired(true).build())
        }.generateKey()
    }

    fun encrypt(bytes: ByteArray): ByteArray {
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.ENCRYPT_MODE, key)
        return cipher.iv + cipher.doFinal(bytes)
    }

    fun decrypt(bytes: ByteArray): ByteArray {
        require(bytes.size >= 29) { "Invalid encrypted record" }
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.DECRYPT_MODE, key, GCMParameterSpec(128, bytes.copyOfRange(0, 12)))
        return cipher.doFinal(bytes.copyOfRange(12, bytes.size))
    }

    fun encryptStream(input: InputStream, destination: File) {
        destination.parentFile?.mkdirs()
        val temp = File(destination.parentFile, destination.name + ".tmp")
        try {
            temp.outputStream().use { output ->
                val cipher = Cipher.getInstance("AES/GCM/NoPadding")
                cipher.init(Cipher.ENCRYPT_MODE, key)
                output.write(cipher.iv)
                CipherOutputStream(output, cipher).use { secured ->
                    input.copyTo(secured, bufferSize = 8192)
                }
            }
            check(temp.renameTo(destination)) { "Could not save encrypted evidence" }
        } finally { temp.delete() }
    }

    fun decryptTo(input: File, output: OutputStream) {
        input.inputStream().use { stream ->
            val iv = ByteArray(12)
            require(stream.read(iv) == 12) { "Invalid evidence header" }
            val cipher = Cipher.getInstance("AES/GCM/NoPadding")
            cipher.init(Cipher.DECRYPT_MODE, key, GCMParameterSpec(128, iv))
            CipherInputStream(stream, cipher).use { it.copyTo(output, 8192) }
        }
    }
}
