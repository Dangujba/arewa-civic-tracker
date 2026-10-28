<?php
namespace Tests\Feature;
use Tests\TestCase;
class ExampleTest extends TestCase
{
    public function test_public_landing_page_uses_square_civic_structure_and_real_icon_libraries(): void
    {
        $this->get('/')->assertOk()
          ->assertSee('Evidence for')
          ->assertSee('better communities')
          ->assertSee('Structured reporting')
          ->assertSee('Yobe in focus')
          ->assertSee('Garin Maje bridge')
          ->assertSee('vendor/fontawesome/all.min.css')
          ->assertSee('vendor/lucide/shield-check.svg')
          ->assertSee('act-structure.css');
    }
}
