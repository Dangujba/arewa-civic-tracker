<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class WorkspaceDesignConsistencyTest extends TestCase {
 use RefreshDatabase;
 private function administrator():User {
  return User::create(['name'=>'Design Administrator','email'=>'design@example.test',
   'password'=>'SufficientTestingPassword','role'=>'admin','is_active'=>true,
   'must_change_password'=>false]);
 }
 public function test_report_filters_and_pagination_layout():void{
  $this->actingAs($this->administrator())->get('/cases')->assertOk()
   ->assertSee('act-inline-filter')->assertSee('act-filter-actions')
   ->assertSee('act-pagination-bar');
 }
 public function test_analytics_filters_and_register():void{
  $this->actingAs($this->administrator())->get('/reports/analytics')->assertOk()
   ->assertSee('act-analytics-filter-grid')->assertSee('act-pagination-bar');
 }
 public function test_staff_table_and_tag_fields():void{
  $admin=$this->administrator();
  $this->actingAs($admin)->get('/admin/users')->assertOk()
   ->assertSee('act-staff-table')->assertSee('act-pagination-bar');
  $this->actingAs($admin)->get('/admin/users/create')->assertOk()->assertSee('data-user-lga-tags');
 }
 public function test_queue_filter_is_aligned():void{
  $this->actingAs($this->administrator())->get('/reviews/queue')->assertOk()
   ->assertSee('act-queue-filter');
 }
}
