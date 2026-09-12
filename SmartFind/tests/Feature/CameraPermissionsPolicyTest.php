<?php

namespace Tests\Feature;

use Tests\TestCase;

class CameraPermissionsPolicyTest extends TestCase
{
    public function test_same_origin_camera_is_allowed_by_permissions_policy(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();

        $policy = (string) $response->headers->get('Permissions-Policy');

        $this->assertStringContainsString('camera=(self)', $policy);
        $this->assertStringNotContainsString('camera=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }
}
