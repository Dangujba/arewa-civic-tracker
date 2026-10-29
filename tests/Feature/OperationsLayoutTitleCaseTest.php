<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsLayoutTitleCaseTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        return User::create([
            'name'=>'Operations Admin',
            'email'=>'operations-layout@example.test',
            'password'=>'test-password',
            'role'=>'admin',
            'is_active'=>true,
            'must_change_password'=>false
        ]);
    }

    public function test_sidebar_menu_labels_are_title_case(): void
    {
        $html=$this->actingAs($this->administrator())->get('/dashboard')
            ->assertOk()->getContent();
        foreach ([
            'Monitoring Reports','New Field Report','Verification Queue',
            'Interactive GIS Map','Users & Roles','Field Operations',
            'Activity Audit','Monitoring Sectors','Security Settings',
            'Public Website','Sign Out','Geographic Intelligence'
        ] as $label) {
            $this->assertStringContainsString($label,$html);
        }
        foreach (['Monitoring reports','Field operations','Users & roles',
                  'Geographic intelligence','Sign out'] as $old) {
            $this->assertStringNotContainsString('>'.$old.'</span>',$html);
        }
    }

    public function test_operations_has_separate_sections_and_preserves_policy_form(): void
    {
        $html=$this->actingAs($this->administrator())
            ->get('/admin/operations')->assertOk()->getContent();
        $this->assertStringContainsString('act-ops-workspace',$html);
        $this->assertStringContainsString('act-ops-paired',$html);
        $this->assertSame(5,substr_count($html,'class="dash-panel act-ops-panel'));
        foreach ([
            'LGA Coverage','Focal Person Performance','MEAL Reviewer Workload',
            'Overdue Reviews','Review Deadline Policy'
        ] as $heading) {
            $this->assertStringContainsString($heading,$html);
        }
        $this->assertStringContainsString('name="review_sla_days"',$html);
        $this->assertStringContainsString('name="require_mfa_admin"',$html);
        $this->assertStringContainsString('name="require_mfa_meal"',$html);
        $this->assertStringContainsString('Save Policy',$html);
    }

    public function test_non_admin_cannot_access_operations(): void
    {
        $focal=User::create([
            'name'=>'Field Officer',
            'email'=>'focal-operations-layout@example.test',
            'password'=>'test-password',
            'role'=>'focal',
            'is_active'=>true,
            'must_change_password'=>false
        ]);
        $this->actingAs($focal)->get('/admin/operations')->assertForbidden();
    }
}
