<?php

namespace Tests\Unit;

use App\Http\Middleware\EnforceSameOrigin;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnforceSameOriginTest extends TestCase
{
    public function test_safe_get_request_is_not_blocked_by_cross_origin_header(): void
    {
        $request = Request::create(
            'http://localhost/login',
            'GET',
            server: [
                'HTTP_ORIGIN' => 'https://evil.example',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ]
        );

        $response = $this->middleware()->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame(200, $response->getStatusCode());
    }


    public function test_same_origin_post_is_allowed(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST',
            server: [
                'HTTP_ORIGIN' => 'http://localhost',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ]
        );

        $response = $this->middleware()->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame(200, $response->getStatusCode());
    }


    public function test_cross_origin_post_is_rejected(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST',
            server: [
                'HTTP_ORIGIN' => 'https://evil.example',
            ]
        );

        $this->assertForbidden(
            fn () => $this->middleware()->handle(
                $request,
                fn (): Response => response('ok')
            )
        );
    }


    public function test_cross_origin_referer_is_rejected_when_origin_is_missing(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST',
            server: [
                'HTTP_REFERER' => 'https://evil.example/form',
            ]
        );

        $this->assertForbidden(
            fn () => $this->middleware()->handle(
                $request,
                fn (): Response => response('ok')
            )
        );
    }



    public function test_relative_referer_is_deferred_to_csrf_layer(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST',
            server: [
                'HTTP_REFERER' => '/profile',
            ]
        );

        $response = $this->middleware()->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame(200, $response->getStatusCode());
    }


    public function test_post_without_origin_or_referer_is_left_to_csrf_layer(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST'
        );

        $response = $this->middleware()->handle(
            $request,
            fn (): Response => response('ok')
        );

        $this->assertSame(200, $response->getStatusCode());
    }


    public function test_fetch_metadata_cross_site_post_is_rejected(): void
    {
        $request = Request::create(
            'http://localhost/profile',
            'POST',
            server: [
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
            ]
        );

        $this->assertForbidden(
            fn () => $this->middleware()->handle(
                $request,
                fn (): Response => response('ok')
            )
        );
    }


    private function middleware(): EnforceSameOrigin
    {
        config()->set(
            'smartfind_security.same_origin.enabled',
            true
        );

        return new EnforceSameOrigin();
    }


    private function assertForbidden(
        callable $callback
    ): void {

        try {
            $callback();

            $this->fail(
                'Expected request to be rejected with HTTP 403.'
            );
        } catch (HttpException $exception) {
            $this->assertSame(
                403,
                $exception->getStatusCode()
            );
        }
    }
}
