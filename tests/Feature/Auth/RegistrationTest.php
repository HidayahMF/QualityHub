<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_no_longer_available(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Unapproved User',
            'email' => 'example@example.test',
            'password' => 'Random#Password2026',
            'password_confirmation' => 'Random#Password2026',
            'role' => 'super_admin',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
