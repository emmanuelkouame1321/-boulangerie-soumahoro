<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_renders(): void
    {
        $this->withoutVite();

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Vue d’ensemble');
        $response->assertSee('Boulangerie', false);
        $response->assertSee('Navigation principale', false);
    }

    public function test_root_redirects_to_dashboard(): void
    {
        $this->withoutVite();

        $this->get('/')->assertRedirect('/dashboard');
    }
}
