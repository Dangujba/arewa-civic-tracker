<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Lga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GisBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_geojson_contains_17_named_yobe_local_governments(): void
    {
        $path = public_path('geo/yobe-lgas.geojson');
        $this->assertFileExists($path);
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('FeatureCollection', $data['type']);
        $this->assertCount(17, $data['features']);
        $names = array_map(fn($f) => $f['properties']['name'], $data['features']);
        $this->assertCount(17, array_unique($names));
        foreach (['Damaturu', 'Potiskum', 'Geidam', 'Nguru', 'Yusufari'] as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_anonymous_person_cannot_access_map_feed_or_page(): void
    {
        $this->get('/map')->assertRedirect('/login');
        $this->get('/map/data')->assertRedirect('/login');
    }

    public function test_authorized_focal_sees_interactive_map_with_lga_filters(): void
    {
        Lga::firstOrCreate(['name' => 'Damaturu'], ['code' => 'DAM']);
        $user = User::create([
            'name' => 'Authorized Test User',
            'email' => 'geo-test@example.test',
            'password' => 'test-password-unused',
            'role' => 'focal',
            'is_active' => true,
        ]);
        $this->actingAs($user)->get('/map')
            ->assertOk()
            ->assertSee('Yobe State Monitoring Map')
            ->assertSee('geo/yobe-lgas.geojson')
            ->assertSee('Damaturu');
    }
}
