<?php
namespace Tests\Feature;

use App\Models\CaseEvidence;
use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvidenceStorageCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function admin():User {
        return User::create(['name'=>'Storage Administrator','email'=>'storage-admin@example.test',
            'password'=>'testing-pass','role'=>'admin','is_active'=>true,'must_change_password'=>false]);
    }

    private function evidence(string $value='Test field photo data'):CaseEvidence {
        $user=$this->admin();
        $lga=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
        $sector=Sector::create(['name'=>'Education']);
        $id=(string)Str::uuid();
        $case=CivicCase::create([
            'uuid'=>$id,'reference'=>'QA-REPORT-001','reporter_id'=>$user->id,
            'client_submission_id'=>(string)Str::uuid(),'payload_hash'=>str_repeat('a',64),
            'type'=>'public_project','title'=>'Synthetic school monitoring report',
            'description'=>'Testing evidence storage in an isolated database',
            'lga_id'=>$lga->id,'community'=>'Test community','sector_id'=>$sector->id,
            'observation_date'=>now()->toDateString(),
        ]);
        $path='cases/'.$id.'/evidence.jpg';
        Storage::disk('local')->put($path,$value);
        return CaseEvidence::create([
            'case_id'=>$case->id,'uploaded_by'=>$user->id,'disk'=>'local',
            'path'=>$path,'original_name'=>'evidence.jpg','mime_type'=>'image/jpeg',
            'size_bytes'=>strlen($value),'sha256'=>hash('sha256',$value),
        ]);
    }

    private function r2Configured():void {
        foreach(['key'=>'dummy','secret'=>'dummy-secret','bucket'=>'dummy-bucket',
            'endpoint'=>'https://test.account.r2.cloudflarestorage.com'] as $key=>$value) {
            config()->set('filesystems.disks.r2.'.$key,$value);
        }
    }

    public function test_administrator_can_see_evidence_storage_and_non_admin_cannot():void {
        $admin=$this->admin();
        $this->actingAs($admin)->get(route('admin.operations.storage'))->assertOk()
            ->assertSee('Evidence Storage')->assertSee('Awaiting Configuration')
            ->assertSee('act:evidence-storage')->assertSee('Verify Private R2 Connection');
        $focal=User::create(['name'=>'Field Officer','email'=>'storage-field@example.test',
            'password'=>'test-pass','role'=>'focal','is_active'=>true,
            'must_change_password'=>false]);
        $this->actingAs($focal)->get(route('admin.operations.storage'))->assertForbidden();
        $this->post(route('admin.operations.storage.check'))->assertForbidden();
    }

    public function test_audit_and_preview_never_change_evidence():void {
        Storage::fake('local'); Storage::fake('r2');
        $file=$this->evidence(); $path=$file->path;
        $this->artisan('act:evidence-storage',['--audit'=>true])->assertExitCode(0);
        $this->artisan('act:evidence-storage',['--migrate-r2'=>true])->assertExitCode(0);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('local')->assertExists($path);
        Storage::disk('r2')->assertMissing($path);
    }

    public function test_r2_transfer_validates_sha_then_updates_record_and_retains_local():void {
        Storage::fake('local'); Storage::fake('r2');
        $file=$this->evidence(); $path=$file->path; $this->r2Configured();
        $this->artisan('act:evidence-storage',['--migrate-r2'=>true,'--execute'=>true])
            ->assertExitCode(0);
        $this->assertSame('r2',$file->fresh()->disk);
        Storage::disk('r2')->assertExists($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame(Storage::disk('local')->get($path),Storage::disk('r2')->get($path));
        $this->artisan('act:evidence-storage',['--audit'=>true])->assertExitCode(0);
        $this->assertSame(1,CaseEvidence::count());
    }

    public function test_checksum_mismatch_blocks_transfer_without_touching_database():void {
        Storage::fake('local'); Storage::fake('r2');
        $file=$this->evidence(); $this->r2Configured();
        Storage::disk('local')->put($file->path,'tampered content');
        $this->artisan('act:evidence-storage',['--migrate-r2'=>true,'--execute'=>true])
            ->assertExitCode(1);
        $this->assertSame('local',$file->fresh()->disk);
        Storage::disk('r2')->assertMissing($file->path);
        $this->artisan('act:evidence-storage',['--audit'=>true])->assertExitCode(1);
    }

    public function test_conflicting_remote_object_is_not_deleted_or_trusted():void {
        Storage::fake('local'); Storage::fake('r2');
        $file=$this->evidence(); $this->r2Configured();
        Storage::disk('r2')->put($file->path,'unrelated document');
        $this->artisan('act:evidence-storage',['--migrate-r2'=>true,'--execute'=>true])
            ->assertExitCode(1);
        $this->assertSame('local',$file->fresh()->disk);
        $this->assertSame('unrelated document',Storage::disk('r2')->get($file->path));
    }

    public function test_r2_connection_checks_use_private_object_and_remove_probe():void {
        Storage::fake('local'); Storage::fake('r2');
        $user=$this->admin();
        $this->actingAs($user)->post(route('admin.operations.storage.check'))->assertSessionHasErrors('storage');
        $this->r2Configured();
        $this->actingAs($user)->post(route('admin.operations.storage.check'))
            ->assertRedirect()->assertSessionHas('message');
        $this->assertSame([],Storage::disk('r2')->allFiles('arewa-health-check'));
    }

    public function test_invalid_limit_and_unconfigured_live_migration_are_blocked():void {
        Storage::fake('local'); Storage::fake('r2');
        $this->evidence();
        $this->artisan('act:evidence-storage',['--audit'=>true,'--limit'=>0])->assertExitCode(1);
        $this->artisan('act:evidence-storage',['--migrate-r2'=>true,'--execute'=>true])->assertExitCode(1);
    }
}
