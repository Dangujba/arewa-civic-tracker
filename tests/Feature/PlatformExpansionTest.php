<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Lga;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
class PlatformExpansionTest extends TestCase {
 use RefreshDatabase;
 private function user(string $role):User{return User::create(['name'=>'Tester','email'=>Str::random(10).'@example.test','password'=>'a-long-test-password','role'=>$role,'is_active'=>true]);}
 public function test_focal_cannot_access_administration_or_export():void{$u=$this->user('focal');$this->actingAs($u)->get('/admin/users')->assertForbidden();$this->actingAs($u)->get('/admin/sectors')->assertForbidden();$this->actingAs($u)->get('/cases/export.csv')->assertForbidden();}
 public function test_admin_can_create_a_focal_person_and_manage_sectors():void{$admin=$this->user('admin');$this->actingAs($admin)->post('/admin/users',['name'=>'Field Officer','email'=>'field@example.test','role'=>'focal','password'=>'Strong-Password-123','password_confirmation'=>'Strong-Password-123'])->assertRedirect('/admin/users');$this->assertDatabaseHas('users',['email'=>'field@example.test','role'=>'focal']);$this->actingAs($admin)->post('/admin/sectors',['name'=>'Water'])->assertRedirect();$this->assertDatabaseHas('sectors',['name'=>'Water']);}
 public function test_dashboard_loads_for_active_focal():void{$u=$this->user('focal');$this->actingAs($u)->get('/dashboard')->assertOk();}
 public function test_admin_cannot_deactivate_self():void{$u=$this->user('admin');$this->actingAs($u)->put('/admin/users/'.$u->id,['name'=>$u->name,'email'=>$u->email,'role'=>'focal','is_active'=>0])->assertSessionHasErrors('role');$this->assertDatabaseHas('users',['id'=>$u->id,'role'=>'admin','is_active'=>1]);}
}
