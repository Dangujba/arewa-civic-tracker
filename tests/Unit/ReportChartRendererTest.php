<?php

namespace Tests\Unit;

use App\Services\ReportChartRenderer;
use PHPUnit\Framework\TestCase;

class ReportChartRendererTest extends TestCase
{
    public function test_management_charts_are_real_png_images_even_for_empty_filters(): void
    {
        $renderer = new ReportChartRenderer();
        $figures = [
            $renderer->trend(['Aug 26', 'Sep 26', 'Oct 26'], [2, 4, 3]),
            $renderer->statuses(['Approved', 'Submitted'], [3, 2]),
            $renderer->ranking(['Damaturu', 'Potiskum'], [4, 2]),
            $renderer->trend(['Aug 26'], [0]),
            $renderer->statuses([], []),
            $renderer->ranking([], []),
        ];

        foreach ($figures as $figure) {
            $this->assertStringStartsWith('data:image/png;base64,', $figure);
            $png = base64_decode(substr($figure, strlen('data:image/png;base64,')), true);
            $this->assertNotFalse($png);
            $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
            $dimensions = getimagesizefromstring($png);
            $this->assertNotFalse($dimensions);
            $this->assertGreaterThanOrEqual(244, $dimensions[1]);
            $this->assertGreaterThanOrEqual(610, $dimensions[0]);
        }
    }
}
