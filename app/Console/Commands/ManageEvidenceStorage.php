<?php
namespace App\Console\Commands;

use App\Models\CaseEvidence;
use App\Services\EvidenceStorageManager;
use Illuminate\Console\Command;

final class ManageEvidenceStorage extends Command
{
    protected $signature='act:evidence-storage
        {--audit : Verify storage objects against recorded SHA-256 and byte lengths}
        {--migrate-r2 : Prepare or perform a verified local-to-R2 migration}
        {--execute : Write changes for --migrate-r2 (without this flag, only preview)}
        {--limit=100 : Maximum records inspected in this invocation}';
    protected $description='Audit private evidence or safely copy validated objects to Cloudflare R2.';

    public function handle(EvidenceStorageManager $manager):int
    {
        if (!$this->option('audit') && !$this->option('migrate-r2')) {
            $this->error('Choose --audit or --migrate-r2');
            return self::FAILURE;
        }
        if ($this->option('audit') && $this->option('migrate-r2')) {
            $this->error('Choose only one operation per run');
            return self::FAILURE;
        }
        $limit=filter_var($this->option('limit'),FILTER_VALIDATE_INT);
        if ($limit===false || $limit<1 || $limit>1000) {
            $this->error('The limit must be between 1 and 1000');
            return self::FAILURE;
        }
        if ($this->option('execute') && !$this->option('migrate-r2')) {
            $this->error('--execute is only supported with --migrate-r2');
            return self::FAILURE;
        }
        $migrate=(bool)$this->option('migrate-r2');
        $execute=(bool)$this->option('execute');
        if ($migrate && $execute && !$manager->r2Configured()) {
            $this->error('R2 is not configured with a valid HTTPS endpoint and credentials.');
            return self::FAILURE;
        }

        $query=CaseEvidence::query();
        if ($migrate) $query->where('disk','local');
        $total=(clone $query)->count();
        $scanned=0; $moved=0; $failed=0;
        $query->orderBy('id')->limit($limit)->get()->each(function(CaseEvidence $evidence)
            use($manager,$migrate,$execute,&$scanned,&$moved,&$failed) {
            $scanned++;
            try {
                if ($migrate && !$execute) {
                    // A dry-run checks the source without touching the remote or the DB.
                    $manager->verify($evidence,'local');
                    $this->line("#{$evidence->id}: eligible for R2");
                } elseif ($migrate) {
                    $result=$manager->migrateToR2($evidence);
                    if ($result==='migrated') $moved++;
                    $this->line("#{$evidence->id}: {$result}");
                } else {
                    $manager->verify($evidence);
                    $this->line("#{$evidence->id}: OK ({$evidence->disk})");
                }
            } catch (\Throwable $error) {
                $failed++;
                $this->error("#{$evidence->id}: {$error->getMessage()}");
            }
        });
        $this->info("Checked {$scanned} of {$total} matching records. Moved {$moved}. Errors {$failed}.");
        if ($migrate && !$execute)
            $this->warn('DRY RUN: no storage or database changes were made. Pass --execute to perform a verified copy.');
        if ($migrate && $execute)
            $this->warn('Verified local originals are retained for recovery. Do not delete them until acceptance and backup verification.');
        return $failed?self::FAILURE:self::SUCCESS;
    }
}
