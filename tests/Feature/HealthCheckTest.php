<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_application_boots_and_login_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}