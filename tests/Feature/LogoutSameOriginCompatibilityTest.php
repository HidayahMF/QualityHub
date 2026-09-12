<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutSameOriginCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_without_custom_same_origin_guard_blocking_the_request(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                // Browser/privacy mode dapat mengirim nilai Origin yang tidak
                // berguna untuk guard tambahan. Logout tetap dilindungi CSRF
                // oleh middleware web pada runtime production.
                'Origin' => 'null',
                'Sec-Fetch-Site' => 'same-origin',
            ])
            ->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
