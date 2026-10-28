<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'name'=>'QA Administrator', 'email'=>'admin-qa@example.test',
            'password'=>'Admin-Strong-Secret!2026','role'=>'admin',
            'is_active'=>true,'must_change_password'=>false,
        ]);
    }

    public function test_end_to_end_admin_focal_meal_approval_map_dashboard_and_evidence(): void
    {
        Storage::fake('local');
        $admin=$this->makeAdmin();
        $this->actingAs($admin)->post('/admin/users',[
            'name'=>'QA Field Officer','email'=>'focal-qa@example.test','role'=>'focal',
            'password'=>'Focal-Temporary!2026','password_confirmation'=>'Focal-Temporary!2026',
        ])->assertRedirect('/admin/users');
        $this->actingAs($admin)->post('/admin/users',[
            'name'=>'QA MEAL Officer','email'=>'meal-qa@example.test','role'=>'meal',
            'password'=>'Meal-Temporary!2026','password_confirmation'=>'Meal-Temporary!2026',
        ])->assertRedirect('/admin/users');
        $focal=User::where('role','focal')->firstOrFail();
        $meal=User::where('role','meal')->firstOrFail();
        $this->assertTrue($focal->must_change_password);
        $this->assertTrue($meal->must_change_password);

        $this->actingAs($focal)->get('/cases/new')->assertRedirect('/account/security');
        $this->actingAs($focal)->post('/account/security/password',[
            'current_password'=>'Focal-Temporary!2026',
            'password'=>'Focal-New-Strong!2026',
            'password_confirmation'=>'Focal-New-Strong!2026',
        ])->assertRedirect('/dashboard');
        $focal->refresh();
        $this->assertFalse($focal->must_change_password);
        $this->actingAs($focal)->get('/cases/new')->assertOk();
        $lga=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
        $sector=Sector::firstOrCreate(['name'=>'Education']);
        $payload=[
            'client_submission_id'=>(string)Str::uuid(),
            'type'=>'public_project',
            'title'=>'QA TEST ONLY: school rehabilitation observation',
            'description'=>'Synthetic end-to-end test record; not a real civic report.',
            'lga_id'=>$lga->id,
            'community'=>'SYNTHETIC QA COMMUNITY',
            'sector_id'=>$sector->id,
            'observation_date'=>now()->toDateString(),
            'progress_status'=>'ongoing',
            'latitude'=>11.7480,'longitude'=>11.9660,
        ];
        $created=$this->actingAs($focal)->postJson('/cases',$payload)->assertCreated()
            ->assertJsonPath('verification_status','submitted');
        $caseId=$created->json('id');
        $reference=$created->json('reference');
        $this->assertStringContainsString('/cases/'.$caseId,$created->json('redirect'));
        $this->actingAs($focal)->postJson('/cases',$payload)->assertCreated();
        $this->assertDatabaseCount('civic_cases',1);

        $this->actingAs($focal)->post('/cases/'.$caseId.'/evidence',[
            'evidence'=>UploadedFile::fake()->image('qa-field-photo.jpg',640,480),
        ])->assertRedirect('/cases/'.$caseId);
        $case=CivicCase::findOrFail($caseId);
        $evidence=$case->evidence()->firstOrFail();
        Storage::disk('local')->assertExists($evidence->path);
        $this->actingAs($focal)->get('/evidence/'.$evidence->id.'/download')->assertOk();

        $this->actingAs($meal)->get('/reviews/queue')->assertRedirect('/account/security');
        $this->actingAs($meal)->post('/account/security/password',[
            'current_password'=>'Meal-Temporary!2026',
            'password'=>'Meal-New-Strong!2026',
            'password_confirmation'=>'Meal-New-Strong!2026',
        ])->assertRedirect('/dashboard');
        $this->actingAs($meal)->get('/reviews/queue')->assertOk()->assertSee($reference);
        $this->actingAs($meal)->get('/cases/'.$caseId)->assertOk()
            ->assertDontSee('<option value="approved">',false);
        $this->actingAs($meal)->postJson('/cases/'.$caseId.'/review',['status'=>'under_review'])
            ->assertOk()->assertJsonPath('reload',true);
        $this->actingAs($meal)->postJson('/cases/'.$caseId.'/review',['status'=>'verified'])
            ->assertOk()->assertJsonPath('status','verified');
        $this->actingAs($meal)->postJson('/cases/'.$caseId.'/review',['status'=>'approved'])
            ->assertForbidden();
        $this->actingAs($admin)->get('/reviews/queue?status=verified')->assertOk()->assertSee($reference);
        $this->actingAs($admin)->postJson('/cases/'.$caseId.'/review',['status'=>'approved'])
            ->assertOk()->assertJsonPath('status','approved');

        $this->assertDatabaseHas('civic_cases',['id'=>$caseId,'verification_status'=>'approved']);
        $this->assertDatabaseCount('case_events',5);
        $this->actingAs($admin)->getJson('/map/data?verification_status=approved')
            ->assertOk()->assertJsonCount(1,'features')
            ->assertJsonPath('features.0.properties.reference',$reference)
            ->assertJsonPath('features.0.properties.verification_status','approved');
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee($reference)->assertSee('Approved records')
            ->assertSee('Damaturu');
        $csv=$this->actingAs($admin)->get('/cases/export.csv')->assertOk();
        $this->assertStringContainsString($reference,$csv->streamedContent());
        $this->actingAs($focal)->get('/reviews/queue')->assertForbidden();
    }

    public function test_password_rotation_is_required_and_wrong_current_password_is_rejected(): void
    {
        $admin=User::create([
            'name'=>'Setup Admin','email'=>'setup-qa@example.test',
            'password'=>'Temporary-Start!2026','role'=>'admin',
            'is_active'=>true,'must_change_password'=>true,
        ]);
        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/account/security');
        $this->actingAs($admin)->get('/account/security')->assertOk()->assertSee('Set your own password');
        $this->actingAs($admin)->post('/account/security/password',[
            'current_password'=>'wrong-password','password'=>'Another-Strong!2026',
            'password_confirmation'=>'Another-Strong!2026',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue($admin->fresh()->must_change_password);
        $this->actingAs($admin)->post('/account/security/password',[
            'current_password'=>'Temporary-Start!2026','password'=>'Another-Strong!2026',
            'password_confirmation'=>'Another-Strong!2026',
        ])->assertRedirect('/dashboard');
        $this->assertFalse($admin->fresh()->must_change_password);
        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }
}
