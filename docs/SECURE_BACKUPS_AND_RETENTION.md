# Arewa Civic Tracker private backup and retention operations

The software includes the Laravel CLI commands `act:secure-backup`, `act:secure-backup --verify=/path/to/archive.tar.gpg`, and `act:privacy-maintenance`. The scheduler runs backups daily at **02:15 WAT** and expired authentication cleanup at **03:15 WAT**. The existing Laravel scheduler invocation must run every minute.

## Before enabling backups

Create an application-user-readable random passphrase **outside the web root** with permission 0600. Set `ACT_BACKUP_KEY_FILE=/secure/path/backup-key` in the protected application environment. Optionally set `ACT_BACKUP_DIR=/private/backup/destination`. Do not commit or email the key. Copy the key to an independently controlled recovery location; losing it makes encrypted snapshots unrecoverable. Ensure the destination has sufficient space and a separately protected off-server copy strategy.

Under the application Unix account, execute `php artisan act:secure-backup`. The command exports MySQL and private application files to temporary protected staging, creates an AES-256 GPG archive and validates it against recorded hashes before reporting success. It does **not** delete any case, evidence, or audit records.

To verify a snapshot: `php artisan act:secure-backup --verify=/private/backup/destination/arewa-XXXX.tar.gpg`. It decrypts into a temporary protected directory, validates manifest SHA-256 hashes and checks the evidence archive structure. Full restoration must be performed offline in an isolated recovery environment after approval; never restore directly onto a live database without a snapshot, change control and data-consistency checks.

## Retention

Only expired Sanctum access tokens and web sessions older than 30 days are automatically purged. Monitoring reports, evidence, staff identities, revisions and audit events are **not** auto-deleted. RAWYOD must approve field-data retention and erasure policy before any destructive routine is introduced.

## Limitations

Snapshots stored on the same VPS do not protect against loss of that VPS. This code cannot supply offsite disaster recovery by itself. Real recovery must be validated using an isolated server and a separately held encryption key. Cloudflare R2 is not required by these commands and is not activated by them.

## Isolated recovery extraction

Set `ACT_RECOVERY_DIR=/var/backups/arewa-recovery` (outside the application and public web root), owned by the application user with 0700 permissions. Run:

`php artisan act:backup-restore-sandbox arewa-YYYYMMDD-hhmmss-xxxxxx.tar.gpg sandbox-test --confirm-isolated`

This confirms the archive manifest hashes, checks entry paths, and unpacks the database SQL and private evidence into an isolated private directory. It **never imports SQL into the live database or replaces active evidence**. Review it on a separate authorized recovery host and destroy the decrypted temporary data when finished. The decryption key must be protected independently from the VPS.
