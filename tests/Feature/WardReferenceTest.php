<?php
namespace Tests\Feature;

use App\Models\CivicCase;
use App\Models\Lga;
use App\Models\User;
use App\Models\Sector;
use App\Models\Ward;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\YobeWardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WardReferenceTest extends TestCase {
    use RefreshDatabase;

    public function test_17_yobe_lgas_have_178_searchable_wards_and_reseeding_is_idempotent():void {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(17,Lga::count());
        $this->assertSame(178,Ward::count());
        $damaturu=Lga::where('name','Damaturu')->firstOrFail();
        $this->assertSame(11,Ward::where('lga_id',$damaturu->id)->count());
        $this->assertTrue(Ward::where('lga_id',$damaturu->id)->where('name','Damaturu Central')->exists());
        $this->seed(YobeWardSeeder::class);
        $this->assertSame(178,Ward::count());
    }

    public function test_ward_and_community_are_separate_and_manual_ward_is_preserved():void {
        $this->seed(DatabaseSeeder::class);
        $lga=Lga::where('name','Gujba')->firstOrFail();
        $sector=Sector::firstOrFail();
        $user=User::create([
          'name'=>'Ward reporter','email'=>'ward-tester@example.test',
          'password'=>'test-password','role'=>'focal','is_active'=>true,
          'must_change_password'=>false
        ]);
        $case=CivicCase::create([
          'uuid'=>(string)Str::uuid(),'reference'=>'ACT-YOB-WARD-TEST',
          'reporter_id'=>$user->id,'client_submission_id'=>(string)Str::uuid(),
          'payload_hash'=>Str::random(64),
          'type'=>'public_project','title'=>'Community health centre',
          'description'=>'Review of construction progress in rural community.',
          'lga_id'=>$lga->id,'ward'=>'Unlisted Local Ward',
          'community'=>'Maju Village','sector_id'=>$sector->id,
          'observation_date'=>today()->toDateString(),
          'progress_status'=>'ongoing','verification_status'=>'submitted',
        ]);
        $this->assertSame('Unlisted Local Ward',$case->fresh()->ward);
        $this->assertSame('Maju Village',$case->fresh()->community);
        $this->assertSame(178,Ward::count());
    }
}
