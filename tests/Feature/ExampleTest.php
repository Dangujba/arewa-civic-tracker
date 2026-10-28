<?php
namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_public_landing_page_renders_with_real_navigation(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Stronger communities', false)
            ->assertSee('Garin Maje bridge')
            ->assertSee('Access reporting portal');
    }
}
