<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePhotoAndSpinnerTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email='my-profile@example.test'): User {
        return User::create([
            'name'=>'Field Officer','email'=>$email,
            'password'=>'test-password','role'=>'focal',
            'is_active'=>true,'must_change_password'=>false,
        ]);
    }

    public function test_login_has_no_unnecessary_description_and_account_has_profile_navigation(): void
    {
        $this->get('/login')->assertOk()
            ->assertDontSee('Use the credentials provided by your RAWYOD administrator.');
        $this->actingAs($this->user())->get('/dashboard')->assertOk()
            ->assertSee('My Profile')->assertSee('account/profile');
        $this->get('/account/profile')->assertOk()
            ->assertSee('Upload Photo')->assertSee('Profile Photo')
            ->assertSee('No photo selected');
    }

    public function test_profile_photo_upload_is_private_and_updates_avatar(): void
    {
        Storage::fake('local');
        $user=$this->user();
        $this->actingAs($user)->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('portrait.jpg',220,220),
        ])->assertRedirect(route('account.profile'));
        $path=$user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('profile-photos/',$path);
        Storage::disk('local')->assertExists($path);
        $this->get(route('account.profile.photo'))
            ->assertOk()->assertHeader('Content-Type','image/jpeg');
        $cacheControl=$this->get(route('account.profile.photo'))->headers->get('Cache-Control');
        $this->assertStringContainsString('private',$cacheControl);
        $this->assertStringContainsString('no-store',$cacheControl);
        $this->get(route('account.profile'))->assertOk()
            ->assertSee(route('account.profile.photo'),false);
        $this->get('/dashboard')->assertOk()->assertSee(route('account.profile.photo'),false);
        $this->assertDatabaseHas('admin_activity_logs',[
            'actor_id'=>$user->id,'action'=>'account.profile_photo_updated',
        ]);
    }

    public function test_new_photo_replaces_previous_file_and_remove_restores_initials(): void
    {
        Storage::fake('local');
        $user=$this->user();
        $this->actingAs($user)->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('first.png',90,90),
        ])->assertRedirect();
        $first=$user->fresh()->profile_photo_path;
        $this->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('second.jpg',90,90),
        ])->assertRedirect();
        $second=$user->fresh()->profile_photo_path;
        $this->assertNotEquals($first,$second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
        $this->delete(route('account.profile.photo.remove'))->assertRedirect(route('account.profile'));
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($second);
        $this->get(route('account.profile.photo'))->assertNotFound();
    }

    public function test_invalid_files_are_rejected_without_overwriting_photo(): void
    {
        Storage::fake('local');
        $user=$this->user();
        $this->actingAs($user)->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->create('script.svg',5,'image/svg+xml')
        ])->assertSessionHasErrors('photo');
        $this->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('large.png')->size(2100)
        ])->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_photo_is_not_visible_to_guests_or_another_user(): void
    {
        Storage::fake('local');
        $owner=$this->user();
        $this->actingAs($owner)->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('secret.jpg')
        ])->assertRedirect();
        $ownerPath=$owner->fresh()->profile_photo_path;
        $this->post(route('logout'));
        $this->get(route('account.profile.photo'))->assertRedirect(route('login'));
        $other=$this->user('other-profile@example.test');
        $this->actingAs($other)->get(route('account.profile.photo'))->assertNotFound();
        Storage::disk('local')->assertExists($ownerPath);
    }

    public function test_photo_routes_are_unavailable_before_initial_password_change(): void
    {
        $user=$this->user();
        $user->forceFill(['must_change_password'=>true])->save();
        $this->actingAs($user)->get(route('account.profile'))->assertRedirect(route('account.password.form'));
        $this->post(route('account.profile.photo.upload'),[
            'photo'=>UploadedFile::fake()->image('pre-login.jpg'),
        ])->assertRedirect(route('account.password.form'));
    }
}
