<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTransparencyTest extends TestCase
{
    use RefreshDatabase;
    private function actor(string $role):User {
        return User::create(['name'=>'Privacy '.$role,'email'=>Str::random(14).'@example.test',
            'password'=>'Example-Strong-Pass','role'=>$role,'is_active'=>true,'must_change_password'=>false]);
    }
    private function report(string $status='approved'):CivicCase {
        $owner=$this->actor('focal');
        $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
        $sector=Sector::firstOrCreate(['name'=>'Health']);
        return CivicCase::create([
            'uuid'=>(string)Str::uuid(),'reference'=>'ACT-YOB-'.Str::upper(Str::random(10)),
            'client_submission_id'=>(string)Str::uuid(),'payload_hash'=>Str::random(64),
            'reporter_id'=>$owner->id,'type'=>'public_project',
            'title'=>'Public clinic monitoring project',
            'description'=>'PRIVATE: Resident phone number 08012345678',
            'observations'=>'PRIVATE REVIEW COMMENTS','duty_bearers'=>'PRIVATE PERSON NAME',
            'lga_id'=>$lga->id,'sector_id'=>$sector->id,'community'=>'Central Ward',
            'observation_date'=>today()->toDateString(),'progress_status'=>'ongoing',
            'verification_status'=>$status,'latitude'=>11.7512345,'longitude'=>11.9654321,
        ]);
    }
    public function test_anonymous_view_has_no_registration_and_unpublished_cases_are_hidden():void {
        $case=$this->report();
        $this->get('/transparency')->assertOk()->assertDontSee($case->title)
            ->assertSee('No account or registration required.');
        $this->getJson('/transparency/map-data')->assertOk()->assertJsonCount(0,'features');
        $this->get('/')->assertOk()->assertSee('/transparency');
        $this->get('/cases/'.$case->id)->assertRedirect(route('login'));
    }
    public function test_publishing_requires_admin_approved_case_and_consent():void {
        $case=$this->report();
        $focal=$this->actor('focal');$admin=$this->actor('admin');
        $url='/admin/cases/'.$case->id.'/public-visibility';
        $this->post($url,['action'=>'publish','confirm_public'=>'1'])->assertRedirect(route('login'));
        $this->actingAs($focal)->post($url,['action'=>'publish','confirm_public'=>'1'])->assertForbidden();
        $this->actingAs($admin)->post($url,['action'=>'publish'])->assertSessionHasErrors('confirm_public');
        $this->actingAs($admin)->post($url,['action'=>'publish','confirm_public'=>'1'])->assertRedirect();
        $this->assertNotNull($case->fresh()->published_at);
        $this->assertDatabaseHas('case_events',['case_id'=>$case->id,'event_type'=>'public_published']);
        $pending=$this->report('submitted');
        $this->actingAs($admin)->post('/admin/cases/'.$pending->id.'/public-visibility',
            ['action'=>'publish','confirm_public'=>'1'])->assertSessionHasErrors('action');
    }
    public function test_public_view_has_only_allowlisted_metadata_and_approximate_map_location():void {
        $case=$this->report();
        $admin=$this->actor('admin');
        $this->actingAs($admin)->post('/admin/cases/'.$case->id.'/public-visibility',
            ['action'=>'publish','confirm_public'=>'1'])->assertRedirect();
        auth()->logout();
        $this->get('/transparency')->assertOk()->assertSee($case->title)->assertSee('Central Ward')
            ->assertDontSee('Resident phone number')->assertDontSee('PRIVATE PERSON NAME')
            ->assertDontSee('PRIVATE REVIEW COMMENTS')->assertDontSee('11.7512345');
        $response=$this->getJson('/transparency/map-data')->assertOk()->assertJsonCount(1,'features');
        $response->assertJsonPath('features.0.geometry.coordinates',[11.97,11.75]);
        foreach(['PRIVATE','reporter_id','description','observations','evidence',
            'actor_id','published_by','11.7512345'] as $value)
            $this->assertStringNotContainsString($value,$response->getContent());
    }
    public function test_unpublish_and_revision_revoke_public_visibility():void {
        $case=$this->report();$admin=$this->actor('admin');
        $url='/admin/cases/'.$case->id.'/public-visibility';
        $this->actingAs($admin)->post($url,['action'=>'publish','confirm_public'=>'1'])->assertRedirect();
        $case->refresh()->update(['progress_status'=>'completed']);
        $this->assertNull($case->fresh()->published_at);
        $this->actingAs($admin)->post($url,['action'=>'publish','confirm_public'=>'1'])->assertRedirect();
        $this->actingAs($admin)->post($url,['action'=>'unpublish'])->assertRedirect();
        $this->assertNull($case->fresh()->published_at);
        $this->getJson('/transparency/map-data')->assertOk()->assertJsonCount(0,'features');
    }
}
