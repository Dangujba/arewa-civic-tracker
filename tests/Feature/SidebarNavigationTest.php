<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::create([
            'name' => 'Sidebar '.ucfirst($role),
            'email' => Str::random(9).'@example.test',
            'password' => 'unused-test-password',
            'is_active' => true,
            'role' => $role,
        ]);
    }

    public function test_admin_sidebar_links_to_administration_and_review(): void
    {
        $this->actingAs($this->staff('admin'))->get('/dashboard')
            ->assertOk()
            ->assertSee('id="portal-sidebar"', false)
            ->assertSee('Users & roles', false)
            ->assertSee('Verification queue')
            ->assertSee('Interactive GIS map')
            ->assertSee('act-sidebar.js');
    }

    public function test_focal_sidebar_does_not_expose_admin_or_review_links(): void
    {
        $this->actingAs($this->staff('focal'))->get('/dashboard')
            ->assertOk()
            ->assertSee('New field report')
            ->assertSee('Interactive GIS map')
            ->assertDontSee('Users & roles', false)
            ->assertDontSee('Verification queue');
    }

    public function test_meal_reviewers_have_queue_but_not_user_management(): void
    {
        $this->actingAs($this->staff('meal'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Verification queue')
            ->assertDontSee('Users & roles', false)
            ->assertDontSee('New field report');
    }

    public function test_public_login_does_not_display_private_sidebar(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('id="portal-sidebar"', false);
    }
}
