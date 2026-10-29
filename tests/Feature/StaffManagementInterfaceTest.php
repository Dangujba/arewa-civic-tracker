<?php
namespace Tests\Feature;
use App\Models\AdminActivityLog;
use App\Models\Lga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementInterfaceTest extends TestCase {
 use RefreshDatabase;

 private function staff(string $role,string $email):User {
  return User::create(['name'=>'Test '.$role,'email'=>$email,
   'password'=>'A-Strong-Pass-2026','role'=>$role,'is_active'=>true,
   'must_change_password'=>false]);
 }
 public function test_staff_index_is_a_real_table_with_icon_actions_and_create_uses_tag_selector():void {
  $admin=$this->staff('admin','admin@example.test');
  $this->actingAs($admin)->get('/admin/users')->assertOk()
   ->assertSee('act-staff-table')->assertSee('data-user-edit')
   ->assertSee('data-user-delete')->assertSee('fa-pen-to-square')
   ->assertSee('fa-trash-can')->assertDontSee('act-lga-multi')
   ->assertDontSee('Hold Ctrl');
  $this->actingAs($admin)->get('/admin/users/create')->assertOk()
   ->assertSee('data-user-lga-tags')->assertSee('act-user-fields')
   ->assertDontSee('Hold Ctrl')->assertDontSee('Hold Command');
 }
 public function test_admin_can_assign_multiple_lgas_and_delete_unused_staff():void {
  $admin=$this->staff('admin','admin@example.test');
  $a=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  $b=Lga::create(['name'=>'Fika','code'=>'FIK']);
  $this->actingAs($admin)->post('/admin/users',[
   'name'=>'Field Officer','email'=>'officer@example.test','role'=>'focal',
   'password'=>'Initial-Password-2026','password_confirmation'=>'Initial-Password-2026',
   'lga_ids'=>[$a->id,$b->id]
  ])->assertRedirect('/admin/users');
  $officer=User::where('email','officer@example.test')->firstOrFail();
  $this->assertEqualsCanonicalizing([$a->id,$b->id],$officer->assignedLgas()->pluck('lgas.id')->all());
  $this->actingAs($admin)->delete('/admin/users/'.$officer->id)->assertRedirect('/admin/users');
  $this->assertDatabaseMissing('users',['id'=>$officer->id]);
  $this->assertDatabaseHas('admin_activity_logs',['actor_id'=>$admin->id,'action'=>'user.deleted']);
 }
 public function test_self_delete_and_delete_of_historical_account_are_blocked():void {
  $admin=$this->staff('admin','admin@example.test');
  $officer=$this->staff('focal','officer@example.test');
  $this->actingAs($admin)->delete('/admin/users/'.$admin->id)->assertSessionHasErrors('user');
  AdminActivityLog::record($officer,'sample.event',$officer);
  $this->actingAs($admin)->delete('/admin/users/'.$officer->id)->assertSessionHasErrors('user');
  $this->assertDatabaseHas('users',['id'=>$officer->id]);
 }
 public function test_non_admin_cannot_delete_users():void {
  $admin=$this->staff('admin','admin@example.test');
  $focal=$this->staff('focal','focal@example.test');
  $this->actingAs($focal)->delete('/admin/users/'.$admin->id)->assertForbidden();
 }
}
