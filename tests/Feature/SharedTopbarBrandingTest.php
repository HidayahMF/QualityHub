<?php

namespace Tests\Feature;

use Tests\TestCase;

class SharedTopbarBrandingTest extends TestCase
{
    private function layout(): string
    {
        $source = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertIsString($source);

        return $source;
    }

    private function styles(): string
    {
        $source = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($source);

        return $source;
    }

    public function test_shared_header_has_bmc_navy_theme_and_page_title(): void
    {
        $view = $this->layout();
        $css = $this->styles();

        $this->assertStringContainsString('data-qh-brand-topbar', $view);
        $this->assertStringContainsString('class="qualityhub-topbar sticky top-0 z-30 h-20"', $view);
        $this->assertStringContainsString('qh-topbar-title font-bold text-white', $view);
        $this->assertStringContainsString('qh-topbar-subtitle hidden sm:block text-xs text-blue-100', $view);
        $this->assertStringContainsString("request()->routeIs('dashboard')", $view);
        $this->assertStringContainsString("request()->routeIs('departments.*')", $view);
        $this->assertStringContainsString('background: linear-gradient(108deg, #152757', $css);

        $header = explode('</header>', explode('data-qh-brand-topbar', $view, 2)[1], 2)[0];
        $this->assertStringNotContainsString('bg-white/90', $header);
    }

    public function test_decorative_led_edges_respect_motion_accessibility(): void
    {
        $view = $this->layout();
        $css = $this->styles();

        $this->assertStringContainsString('qh-topbar-led--left" aria-hidden="true"', $view);
        $this->assertStringContainsString('qh-topbar-led--right" aria-hidden="true"', $view);
        $this->assertStringContainsString('@keyframes qh-topbar-edge-light', $css);
        $this->assertStringContainsString('@keyframes qh-topbar-rail-light', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('animation: none !important;', $css);
        $this->assertStringContainsString('pointer-events: none;', $css);
    }

    public function test_profile_menu_and_mobile_navigation_are_preserved(): void
    {
        $view = $this->layout();

        foreach ([
            '@click="sidebarOpen = true"',
            'profileOpen = !profileOpen',
            'x-show="profileOpen"',
            "route('profile.photo.show')",
            "route('profile.edit')",
            'Sign Out',
            '{{ $slot }}',
            'qh-topbar-profile-name',
            'qh-topbar-session-chip',
            'qh-topbar-menu-toggle',
        ] as $needle) {
            $this->assertStringContainsString($needle, $view);
        }
    }
}
