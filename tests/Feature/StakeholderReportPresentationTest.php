<?php

namespace Tests\Feature;

use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use App\Services\StakeholderChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StakeholderReportPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'admin'): User
    {
        return User::create([
            'name' => 'Stakeholder Export Test',
            'email' => Str::random(12).'@example.test',
            'password' => 'SyntheticPassword!2026',
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_charts_render_valid_png_when_gd_is_available(): void
    {
        $service = app(StakeholderChartService::class);
        if (!$service->available()) {
            $this->markTestSkipped('PHP GD is required for server-rendered charts.');
        }
        $charts = [
            $service->trend(['Aug 2026','Sep 2026','Oct 2026'],[1,3,2]),
            $service->bars(['Damaturu'=>5,'Gujba'=>2]),
            $service->bars([], '#C69C4C'),
        ];
        foreach ($charts as $image) {
            $this->assertNotNull($image);
            $this->assertStringStartsWith('data:image/png;base64,', $image);
            $bytes = base64_decode(substr($image, strlen('data:image/png;base64,')), true);
            $this->assertNotFalse($bytes);
            $this->assertStringStartsWith("\x89PNG\r\n\x1A\n", $bytes);
            $this->assertGreaterThan(500, strlen($bytes));
        }
    }

    public function test_filtered_management_pdf_contains_presentable_report_and_not_raw_table_only(): void
    {
        $admin = $this->staff();
        $lga = Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
        $sector = Sector::firstOrCreate(['name'=>'Health']);
        $this->actingAs($admin)->postJson('/cases', [
            'client_submission_id'=>(string) Str::uuid(),
            'type'=>'public_project',
            'title'=>'Synthetic stakeholder illustration',
            'description'=>'Synthetic monitoring content used only within the isolated test database.',
            'lga_id'=>$lga->id,
            'sector_id'=>$sector->id,
            'community'=>'QA location',
            'observation_date'=>today()->toDateString(),
            'progress_status'=>'ongoing',
        ])->assertCreated();

        $pdf = $this->actingAs($admin)->get('/reports/export.pdf?lga_id='.$lga->id);
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertGreaterThan(10000, strlen($pdf->getContent()));
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('Content-Type',''));
        $this->assertStringContainsString('stakeholder-report', $pdf->headers->get('Content-Disposition',''));
        $this->actingAs($this->staff('focal'))->get('/reports/export.pdf')->assertForbidden();
    }

    public function test_empty_selection_still_generates_a_nonfictional_pdf(): void
    {
        $admin = $this->staff();
        $result = $this->actingAs($admin)->get('/reports/export.pdf?q=no-such-observation');
        $result->assertOk();
        $this->assertStringStartsWith('%PDF-', $result->getContent());
    }

    public function test_report_interface_exposes_compact_feedback_assets_and_stakeholder_export(): void
    {
        $admin = $this->staff();
        $response = $this->actingAs($admin)->get('/reports/analytics');
        $response->assertOk()
            ->assertSee('act-feedback.css')
            ->assertSee('Export PDF')
            ->assertSee('reports-trend')
            ->assertSee('reports-status');
    }
}
