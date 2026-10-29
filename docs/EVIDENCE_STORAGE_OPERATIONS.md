# Arewa Civic Tracker: evidence storage operations

This guide covers private evidence uploaded through the web portal and native Android API. **Never** place evidence in a public Cloudflare bucket.

## Activation procedure

1. Create a private Cloudflare R2 bucket and scoped API credentials.
2. Set `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, and the HTTPS `R2_ENDPOINT` in the server environment. Keep `EVIDENCE_DISK=local` initially. Never commit credentials.
3. Rebuild the Laravel config cache as the application Unix user.
4. Open **Administration → Evidence Storage** and choose **Verify Private R2 Connection** to write, read back and delete a temporary private probe.
5. Run `php artisan act:evidence-storage --audit` to check local file existence, SHA-256 and size.
6. Run `php artisan act:evidence-storage --migrate-r2` for a dry-run preview.
7. Run `php artisan act:evidence-storage --migrate-r2 --execute --limit=100` in batches after investigating any audit failures.
8. Repeat the audit after the migration; verify authorized web and Android evidence downloads.
9. Only after proving private upload/download on the actual R2 bucket, set `EVIDENCE_DISK=r2` for future uploads. Old evidence continues to use the recorded disk.

## Safety and rollback

The copy is checked against its SHA-256 and original byte length before updating the database record. The original private local evidence remains intact and can be used for manual recovery. Failed or mismatched migrations do not redirect the record. Existing remote objects are not overwritten or deleted on mismatch. Dry-run is the default. The browser never receives storage API keys or performs bulk migrations.

Switching `EVIDENCE_DISK` back to `local` controls *new uploads only* and does not automatically re-point already migrated rows. Restore individual records only after verified local digest checks under controlled administrative change management. Keep database and evidence backups until formally accepted.

## Activation dependency

Actual R2 connection and end-to-end upload validation cannot occur until RAWYOD-approved bucket credentials are provided. Unit tests use fake disks and are not proof of real Cloudflare availability.
