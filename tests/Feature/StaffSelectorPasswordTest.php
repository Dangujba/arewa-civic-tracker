<?php
namespace Tests\Feature;

use App\Models\Lga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffSelectorPasswordTest extends TestCase {
 use RefreshDatabase;
 private function admin():User {
  return User::create([
   'name'=>'QA Administrator','email'=>'qa-admin@example.test',
   'password'=>'initial-admin-password','role'=>'admin',
   'is_active'=>true,'must_change_password'=>false
  ]);
 }
 public function test_lga_widget_loads_after_tom_select_and_initial_password_has_no_minimum_attribute():void {
  $this->actingAs($this->admin());
  $response=$this->get('/admin/users/create')->assertOk();
  $html=$response->getContent();
  $this->assertStringContainsString('data-user-lga-tags',$html);
  $this->assertStringContainsString('vendor/ui/tom-select.js',$html);
  $this->assertStringContainsString('js/act-users.js',$html);
  $this->assertLessThan(strpos($html,'js/act-users.js'),strpos($html,'vendor/ui/tom-select.js'));
  $this->assertStringNotContainsString('minlength="12"',$html);
  $this->assertStringNotContainsString('Minimum 12 characters',$html);
  $this->assertStringNotContainsString('At least 12 characters',$html);
 }
 public function test_staff_creation_accepts_any_non_empty_confirmed_password():void {
  $this->actingAs($this->admin());
  $lga=Lga::create(['name'=>'Damaturu','code'=>'DAM']);
  $this->post('/admin/users',[
   'name'=>'Short Password Staff','email'=>'short-staff@example.test',
   'role'=>'focal','password'=>'abc','password_confirmation'=>'abc',
   'lga_ids'=>[$lga->id]
  ])->assertRedirect(route('admin.users.index'));
  $staff=User::where('email','short-staff@example.test')->firstOrFail();
  $this->assertTrue(Hash::check('abc',$staff->password));
  $this->assertTrue((bool)$staff->must_change_password);
  $this->assertSame([$lga->id],$staff->assignedLgas()->pluck('lgas.id')->all());
 }
 public function test_password_confirmation_still_required():void {
  $this->actingAs($this->admin())->post('/admin/users',[
   'name'=>'Mismatched Staff','email'=>'wrong-pass@example.test',
   'role'=>'meal','password'=>'abc','password_confirmation'=>'different'
  ])->assertSessionHasErrors('password');
  $this->assertDatabaseMissing('users',['email'=>'wrong-pass@example.test']);
 }
 public function test_first_login_password_change_has_no_length_requirement():void {
  $staff=User::create(['name'=>'New Staff','email'=>'first@example.test',
   'password'=>'abc','role'=>'focal','is_active'=>true,'must_change_password'=>true]);
  $this->actingAs($staff)->get('/account/security')->assertOk()
   ->assertDontSee('minlength="12"',false);
  $this->post('/account/security/password',[
   'current_password'=>'abc','password'=>'xy','password_confirmation'=>'xy'
  ])->assertRedirect(route('dashboard'));
  $this->assertTrue(Hash::check('xy',$staff->fresh()->password));
  $this->assertFalse((bool)$staff->fresh()->must_change_password);
 }
}
