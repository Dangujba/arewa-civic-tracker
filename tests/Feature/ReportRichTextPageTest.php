<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportRichTextPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_professional_report_detail_with_safe_quill_markup(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::create([
            'name'=>'Report reviewer',
            'email'=>'report-layout-qa@example.test',
            'password'=>'SecureTestPassword2026!',
            'role'=>'admin',
            'is_active'=>true,
            'must_change_password'=>false,
        ]);
        $case = CivicCase::create([
            'uuid'=>(string)Str::uuid(),
            'client_submission_id'=>(string)Str::uuid(),
            'payload_hash'=>Str::random(64),
            'reference'=>'ACT-YOB-RICH-TEST',
            'reporter_id'=>$admin->id,
            'type'=>'public_project',
            'title'=>'Test infrastructure inspection',
            'description'=>'<h2>Condition</h2><p><strong>School roof</strong> requires attention.</p><script>alert(1)</script>',
            'lga_id'=>Lga::firstOrFail()->id,
            'ward'=>'Test Ward',
            'community'=>'Test Community',
            'sector_id'=>Sector::firstOrFail()->id,
            'observation_date'=>now()->toDateString(),
            'verification_status'=>'submitted',
            'progress_status'=>'ongoing',
        ]);
        $this->actingAs($admin)->get(route('cases.show',$case))->assertOk()
            ->assertSee('act-case-content-grid')
            ->assertSee('Report information')
            ->assertSee('Test infrastructure inspection')
            ->assertSee('<h2>Condition</h2>', false)
            ->assertSee('<strong>School roof</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Print report');
    }

    public function test_create_page_uses_official_quill_editor_assets(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::create([
            'name'=>'Quill editor QA',
            'email'=>'quill-form-qa@example.test',
            'password'=>'SecureTestPassword2026!',
            'role'=>'admin',
            'is_active'=>true,
            'must_change_password'=>false,
        ]);
        $this->actingAs($admin)->get(route('cases.create'))->assertOk()
            ->assertSee('vendor/quill/quill.js')
            ->assertSee('vendor/quill/quill.snow.css')
            ->assertSee('data-act-richtext');
    }
}
