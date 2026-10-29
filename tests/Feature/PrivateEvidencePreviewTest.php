<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use App\Services\CaseEvidenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivateEvidencePreviewTest extends TestCase
{
    use RefreshDatabase;
    private function actor():User {
        return User::create(['name'=>'Evidence auditor','email'=>'evidence-auditor@example.test',
            'password'=>'Example-Strong-Password','role'=>'admin','is_active'=>true,
            'must_change_password'=>false]);
    }
    private function report(User $actor):CivicCase {
        $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
        $sector=Sector::firstOrCreate(['name'=>'Health']);
        return CivicCase::create([
            'uuid'=>(string)Str::uuid(),'reference'=>'ACT-TEST-'.Str::upper(Str::random(8)),
            'client_submission_id'=>(string)Str::uuid(),'payload_hash'=>Str::random(64),
            'reporter_id'=>$actor->id,'type'=>'public_project','title'=>'Evidence preservation test',
            'description'=>'A report with genuine image metadata and a private preview',
            'lga_id'=>$lga->id,'sector_id'=>$sector->id,'community'=>'Central Ward',
            'observation_date'=>today()->toDateString(),'progress_status'=>'observed',
            'verification_status'=>'submitted',
        ]);
    }
    public function test_image_original_is_bit_identical_and_preview_private():void {
        Storage::fake('local');
        $actor=$this->actor();$case=$this->report($actor);
        $file=UploadedFile::fake()->image('audit-original.jpg',1850,1050);
        $original=file_get_contents($file->getRealPath());
        app(CaseEvidenceService::class)->attach($case,$actor,[$file]);
        $evidence=$case->evidence()->firstOrFail();
        $this->assertSame($original,Storage::disk('local')->get($evidence->path));
        $this->assertSame(hash('sha256',$original),$evidence->sha256);
        $this->assertNotNull($evidence->preview_path);
        $preview=Storage::disk('local')->get($evidence->preview_path);
        $this->assertSame(hash('sha256',$preview),$evidence->preview_sha256);
        $this->assertSame(strlen($preview),(int)$evidence->preview_size_bytes);
        $this->assertNotSame($original,$preview);
        $this->get('/evidence/'.$evidence->id.'/preview')->assertRedirect(route('login'));
        $this->actingAs($actor)->get('/evidence/'.$evidence->id.'/preview')->assertOk()
            ->assertHeader('Content-Type','image/webp');
        $this->actingAs($actor)->get('/evidence/'.$evidence->id.'/download')->assertOk();
    }
    public function test_pdf_stays_original_without_preview():void {
        Storage::fake('local');
        $actor=$this->actor();$case=$this->report($actor);
        $file=UploadedFile::fake()->create('notes.pdf',120,'application/pdf');
        app(CaseEvidenceService::class)->attach($case,$actor,[$file]);
        $evidence=$case->evidence()->firstOrFail();
        $this->assertNull($evidence->preview_path);
        $this->assertSame(hash_file('sha256',$file->getRealPath()),$evidence->sha256);
        $this->actingAs($actor)->get('/evidence/'.$evidence->id.'/preview')->assertNotFound();
    }
}
