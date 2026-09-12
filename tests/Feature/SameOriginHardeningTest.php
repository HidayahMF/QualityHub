<?php

namespace Tests\Feature;

use Tests\TestCase;

class SameOriginHardeningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Local development memang boleh mematikan guard tambahan ini lewat .env.
         * Test tetap harus menguji perilaku middleware saat fitur DINYALAKAN,
         * sehingga hasil test tidak tergantung konfigurasi lokal developer.
         */
        config()->set('smartfind_security.same_origin.enabled', true);
    }

    public function test_cross_origin_unsafe_web_request_is_blocked(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'https://evil.example',
                'Sec-Fetch-Site' => 'cross-site',
            ])
            ->post('/login', [
                'email' => 'nobody@example.com',
                'password' => 'invalid-password',
            ]);

        $response->assertForbidden();
    }

    public function test_same_origin_unsafe_web_request_reaches_normal_application_flow(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'http://localhost',
                'Sec-Fetch-Site' => 'same-origin',
            ])
            ->post('/login', []);

        $response
            ->assertSessionHasErrors([
                'email',
                'password',
            ]);
    }

    public function test_safe_get_request_is_not_rejected_by_same_origin_guard(): void
    {
        $response = $this
            ->withHeaders([
                'Origin' => 'https://evil.example',
                'Sec-Fetch-Site' => 'cross-site',
            ])
            ->get('/login');

        $response->assertOk();
    }
}
