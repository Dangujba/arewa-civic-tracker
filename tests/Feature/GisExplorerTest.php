<?php

namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GisExplorerTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'GIS '.ucfirst($role),
            'email' => Str::random(10).'@example.test',
            'password' => 'Unused-Test-Password!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function sample(User $user, string $title, string $type, string $progress): CivicCase
    {
        $lga = Lga::firstOrCreate(['name'=>'Damaturu'], ['code'=>'DAM']);
        $sector = Sector::firstOrCreate(['name'=>'Health']);
        $r = $this->actingAs($user)->postJson('/cases', [
            'client_submission_id'=>(string)Str::uuid(),
            'type'=>$type,
            'title'=>$title,
            'description'=>'Synthetic test report for geographic filter verification.',
            'lga_id'=>$lga->id,
            'sector_id'=>$sector->id,
            'community'=>'QA SITE',
            'observation_date'=>now()->toDateString(),
            'progress_status'=>$progress,
            'latitude'=>11.748,
            'longitude'=>11.966,
        ])->assertCreated();
        return CivicCase::findOrFail($r->json('id'));
    }

    public function test_map_page_contains_filter_explorer_and_toolbar(): void
    {
        $this->actingAs($this->user('admin'))->get('/map')
            ->assertOk()
            ->assertSee('gis-filter-form')
            ->assertSee('gis-report-results')
            ->assertSee('gis-lga-results')
            ->assertSee('gis-measure')
            ->assertSee('gis-export-geojson')
            ->assertSee('act-map.js');
    }

    public function test_filters_use_approved_parameters_and_stay_role_restricted(): void
    {
        $owner=$this->user('focal');
        $other=$this->user('focal');
        $project=$this->sample($owner, 'QA TEST rehabilitation project', 'public_project', 'ongoing');
        $issue=$this->sample($other, 'QA TEST community water issue', 'governance_issue', 'observed');

        $this->actingAs($owner)->getJson('/map/data')
            ->assertOk()->assertJsonCount(1,'features')
            ->assertJsonPath('features.0.properties.reference',$project->reference);
        $this->actingAs($owner)->getJson('/map/data?type=governance_issue')
            ->assertOk()->assertJsonCount(0,'features');
        $this->actingAs($owner)->getJson('/map/data?type=public_project&progress_status=ongoing')
            ->assertOk()->assertJsonCount(1,'features');
        $this->actingAs($owner)->getJson('/map/data?q=rehabilitation')
            ->assertOk()->assertJsonCount(1,'features');
        $this->actingAs($owner)->getJson('/map/data?q=unmatched')
            ->assertOk()->assertJsonCount(0,'features');
        $this->actingAs($this->user('admin'))->getJson('/map/data?type=governance_issue')
            ->assertOk()->assertJsonCount(1,'features')
            ->assertJsonPath('features.0.properties.reference',$issue->reference);
    }

    public function test_map_reports_validation_errors_and_no_store_cache(): void
    {
        $admin=$this->user('admin');
        $this->actingAs($admin)->getJson('/map/data?type=invalid')
            ->assertUnprocessable();
        $this->actingAs($admin)->getJson('/map/data?date_from=2030-01-01')
            ->assertUnprocessable();
        $this->actingAs($admin)->getJson('/map/data')
            ->assertOk()->assertHeader('Cache-Control','no-store, private')
            ->assertJsonPath('meta.visibility','role-restricted');
    }

    public function test_inactive_user_cannot_access_map_page_or_feed(): void
    {
        $user=$this->user('focal');
        $user->update(['is_active'=>false]);
        $this->actingAs($user)->get('/map')->assertForbidden();
        $this->actingAs($user)->getJson('/map/data')->assertForbidden();
    }
}
