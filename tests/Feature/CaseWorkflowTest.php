<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\CivicCase;
use App\Models\CaseEvent;
use App\Models\Lga;
use App\Models\Sector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
class CaseWorkflowTest extends TestCase {
    use RefreshDatabase;
    private function actor(string $role):User{return User::create(['name'=>'Test '.$role,'email'=>Str::random(8).'@example.test','password'=>'not-a-real-password','role'=>$role,'is_active'=>true]);}
    private function payload():array{
        $l=Lga::firstOrCreate(['name'=>'Damaturu'],['code'=>'DAM']);
        $s=Sector::firstOrCreate(['name'=>'Health']);
        return ['client_submission_id'=>(string)Str::uuid(),'type'=>'governance_issue','title'=>'Sample reported observation','description'=>'A labelled synthetic test submission for report validation','lga_id'=>$l->id,'community'=>'TEST ONLY','sector_id'=>$s->id,'observation_date'=>now()->toDateString(),'progress_status'=>'observed'];
    }
    public function test_focal_can_submit_and_duplicate_idempotently():void{
        $actor=$this->actor('focal');$payload=$this->payload();
        $this->actingAs($actor)->postJson('/cases',$payload)->assertCreated()->assertJsonPath('verification_status','submitted');
        $this->actingAs($actor)->postJson('/cases',$payload)->assertCreated();
        $this->assertDatabaseCount('civic_cases',1);$this->assertDatabaseCount('case_events',1);
    }
    public function test_reusing_submission_id_for_different_payload_is_rejected():void{
        $actor=$this->actor('focal');$payload=$this->payload();
        $this->actingAs($actor)->postJson('/cases',$payload)->assertCreated();
        $payload['title']='Different title using the same UUID';
        $this->actingAs($actor)->postJson('/cases',$payload)->assertStatus(409);
        $this->assertDatabaseCount('civic_cases',1);
    }
    public function test_non_owner_cannot_see_case_or_map_marker():void{
        $first=$this->actor('focal');$other=$this->actor('focal');$payload=$this->payload();$payload['latitude']=11.7480;$payload['longitude']=11.9660;
        $response=$this->actingAs($first)->postJson('/cases',$payload)->assertCreated();
        $id=$response->json('id');
        $this->actingAs($other)->get('/cases/'.$id)->assertForbidden();
        $this->actingAs($other)->getJson('/map/data')->assertOk()->assertJsonCount(0,'features');
        $this->actingAs($first)->getJson('/map/data')->assertOk()->assertJsonCount(1,'features');
    }
    public function test_focal_cannot_review_and_meal_cannot_approve():void{
        $focal=$this->actor('focal');$meal=$this->actor('meal');$admin=$this->actor('admin');$payload=$this->payload();
        $id=$this->actingAs($focal)->postJson('/cases',$payload)->json('id');
        $this->actingAs($focal)->postJson('/cases/'.$id.'/review',['status'=>'under_review'])->assertForbidden();
        $this->actingAs($meal)->postJson('/cases/'.$id.'/review',['status'=>'under_review'])->assertOk();
        $this->actingAs($meal)->postJson('/cases/'.$id.'/review',['status'=>'verified'])->assertOk();
        $this->actingAs($meal)->postJson('/cases/'.$id.'/review',['status'=>'approved'])->assertForbidden();
        $this->actingAs($admin)->postJson('/cases/'.$id.'/review',['status'=>'approved'])->assertOk();
        $this->assertDatabaseHas('civic_cases',['id'=>$id,'verification_status'=>'approved']);
        $this->assertDatabaseCount('case_events',4);
    }
    public function test_inactive_user_cannot_access_geographic_feed():void{
        $u=$this->actor('focal');$u->update(['is_active'=>false]);
        $this->actingAs($u)->getJson('/map/data')->assertForbidden();
    }
}
