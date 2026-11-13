<?php

namespace Tests\Feature;

use Tests\TestCase;

class DepartmentConfirmationButtonUiTest extends TestCase
{
    private function viewSource(): string
    {
        $source = file_get_contents(resource_path('views/departments/index.blade.php'));
        $this->assertIsString($source);

        return $source;
    }

    public function test_permanent_delete_button_has_static_visible_red_styles(): void
    {
        $source = $this->viewSource();

        $this->assertSame(1, preg_match(
            '/<button\b[^>]*x-show="destructive"[^>]*>\s*Hapus Permanen\s*<\/button>/s',
            $source,
            $match
        ));

        $button = $match[0];
        $this->assertStringContainsString('bg-rose-700', $button);
        $this->assertStringContainsString('text-white', $button);
        $this->assertStringContainsString('disabled:bg-rose-100', $button);
        $this->assertStringContainsString('disabled:text-rose-800', $button);
        $this->assertStringContainsString('disabled:opacity-100', $button);
        $this->assertStringContainsString('x-bind:disabled="cannotConfirm"', $button);
        $this->assertStringContainsString('x-on:click="confirm"', $button);
        $this->assertStringNotContainsString('x-bind:class=', $button);
    }

    public function test_activation_confirmation_still_uses_static_blue_button(): void
    {
        $source = $this->viewSource();
        $this->assertSame(1, preg_match(
            '/<button\b[^>]*x-show="normalAction"[^>]*>[\s\S]*?<\/button>/',
            $source,
            $match
        ));

        $button = $match[0];
        $this->assertStringContainsString('bg-blue-800', $button);
        $this->assertStringContainsString('text-white', $button);
        $this->assertStringContainsString('x-bind:disabled="cannotConfirm"', $button);
        $this->assertStringContainsString('x-text="confirmText"', $button);
        $this->assertStringContainsString('x-on:click="confirm"', $button);
    }

    public function test_code_validation_stays_enforced_in_client_and_server(): void
    {
        $view = $this->viewSource();
        $js = file_get_contents(resource_path('../resources/js/app.js'));
        $controller = file_get_contents(app_path('Http/Controllers/DepartmentManagementController.php'));

        $this->assertIsString($js);
        $this->assertIsString($controller);

        $this->assertStringContainsString('x-model="typedCode"', $view);
        $this->assertStringContainsString('x-bind:disabled="cannotConfirm"', $view);
        $this->assertStringContainsString('get normalAction()', $js);
        $this->assertStringContainsString('get cannotConfirm()', $js);
        $this->assertStringContainsString('this.typedCode !== this.confirmationCode', $js);
        $this->assertStringContainsString('input.value = this.typedCode', $js);
        $this->assertStringContainsString('hash_equals((string) $locked->code', $controller);
    }
}
