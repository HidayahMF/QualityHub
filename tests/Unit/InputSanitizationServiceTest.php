<?php

namespace Tests\Unit;

use App\Services\InputSanitizationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InputSanitizationServiceTest extends TestCase
{
    private InputSanitizationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InputSanitizationService();
    }

    #[Test]
    public function single_line_removes_script_markup_and_control_characters(): void
    {
        $value = "  <script>alert('x')</script>  Axle\x00\x1F Assy  ";

        $this->assertSame(
            'Axle Assy',
            $this->service->singleLine($value)
        );
    }


    #[Test]
    public function single_line_keeps_word_boundary_across_newline(): void
    {
        $this->assertSame(
            'Axle Assy',
            $this->service->singleLine("Axle\nAssy")
        );
    }

    #[Test]
    public function single_line_removes_unicode_bidi_and_zero_width_characters(): void
    {
        $value = "BRAKE\u{202E}\u{200B} ASSY";

        $this->assertSame(
            'BRAKE ASSY',
            $this->service->singleLine($value)
        );
    }

    #[Test]
    public function multiline_keeps_newlines_but_removes_html(): void
    {
        $value = "Baris 1\n<b>Baris 2</b>\n<script>evil()</script>Baris 3";

        $this->assertSame(
            "Baris 1\nBaris 2\nBaris 3",
            $this->service->multiline($value)
        );
    }

    #[Test]
    public function email_is_trimmed_and_lowercased(): void
    {
        $this->assertSame(
            'quality@bmc.co.id',
            $this->service->email('  Quality@BMC.CO.ID  ')
        );
    }

    #[Test]
    public function like_wildcards_are_escaped(): void
    {
        $this->assertSame(
            '100\\%\\_OK\\\\QC',
            $this->service->escapeLike('100%_OK\\QC')
        );
    }
}
