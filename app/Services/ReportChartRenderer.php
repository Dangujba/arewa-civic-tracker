<?php

namespace App\Services;

/**
 * Renders the exact filtered management aggregates as embedded PNGs.
 * GD images are used because Dompdf cannot reliably render browser canvas charts.
 */
final class ReportChartRenderer
{
    private const GREEN = '#176447';
    private const TEXT = '#294535';
    private const MUTED = '#627869';
    private const GRID = '#e4ebe5';

    private function make(int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);
        imageantialias($image, true);
        imagefill($image, 0, 0, $this->color($image, '#ffffff'));
        return $image;
    }

    private function color(\GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        return imagecolorallocate($image, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
    }

    private function label(string $label, int $length = 24): string
    {
        $text = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label;
        return strlen($text) > $length ? substr($text, 0, $length - 3).'...' : $text;
    }

    private function imageData(\GdImage $image): string
    {
        ob_start();
        imagepng($image, null, 6);
        $data = ob_get_clean();
        imagedestroy($image);
        return 'data:image/png;base64,'.base64_encode($data);
    }

    private function empty(\GdImage $image, int $width, int $height): void
    {
        $text = 'No matching reports in this selection';
        imagestring($image, 3, max(12, (int)(($width - strlen($text) * 7) / 2)), (int)($height / 2), $text, $this->color($image, self::MUTED));
    }

    public function trend(array $labels, array $counts): string
    {
        $width = 610;
        $height = 244;
        $image = $this->make($width, $height);
        $dark = $this->color($image, self::TEXT);
        $muted = $this->color($image, self::MUTED);
        $grid = $this->color($image, self::GRID);
        $green = $this->color($image, self::GREEN);
        if (!array_sum($counts)) {
            $this->empty($image, $width, $height);
            return $this->imageData($image);
        }
        $left = 43;
        $right = 590;
        $top = 23;
        $bottom = 199;
        $max = max(1, max($counts));
        $ceiling = max(4, (int)ceil($max / 4) * 4);
        for ($step = 0; $step <= 4; $step++) {
            $value = (int)round($ceiling - ($ceiling * $step / 4));
            $y = (int)round($top + ($bottom - $top) * $step / 4);
            imageline($image, $left, $y, $right, $y, $grid);
            imagestring($image, 2, 7, $y - 7, (string)$value, $muted);
        }
        $count = max(1, count($counts));
        $old = null;
        for ($i = 0; $i < $count; $i++) {
            $x = (int)round($left + ($right - $left) * $i / max(1, $count - 1));
            $y = (int)round($bottom - ((int)($counts[$i] ?? 0) / $ceiling) * ($bottom - $top));
            if ($old !== null) {
                imagesetthickness($image, 3);
                imageline($image, $old[0], $old[1], $x, $y, $green);
                imagesetthickness($image, 1);
            }
            imagefilledellipse($image, $x, $y, 7, 7, $green);
            if ($i % 2 === 0 || $i === $count - 1) {
                $month = $this->label((string)($labels[$i] ?? ''), 8);
                imagestring($image, 2, max(1, $x - 20), $bottom + 13, $month, $dark);
            }
            $old = [$x, $y];
        }
        return $this->imageData($image);
    }

    public function statuses(array $labels, array $counts, array $colors = []): string
    {
        $width = 610;
        $height = 244;
        $image = $this->make($width, $height);
        $text = $this->color($image, self::TEXT);
        $muted = $this->color($image, self::MUTED);
        $total = array_sum($counts);
        if (!$total) {
            $this->empty($image, $width, $height);
            return $this->imageData($image);
        }
        $defaultColors = ['#b38943', '#447fa1', '#c47b49', '#348c72', '#206447', '#ba605a'];
        $cx = 138;
        $cy = 122;
        $angle = -90.0;
        foreach ($counts as $index => $value) {
            $value = max(0, (int)$value);
            if (!$value) continue;
            $end = $angle + $value / $total * 360;
            $color = $this->color($image, $colors[$index] ?? $defaultColors[$index % count($defaultColors)]);
            imagefilledarc($image, $cx, $cy, 204, 204, (int)round($angle), (int)round($end), $color, IMG_ARC_PIE);
            $angle = $end;
        }
        imagefilledellipse($image, $cx, $cy, 116, 116, $this->color($image, '#ffffff'));
        $totalText = number_format($total);
        imagestring($image, 5, $cx - strlen($totalText) * 4, $cy - 14, $totalText, $text);
        imagestring($image, 2, $cx - 22, $cy + 8, 'REPORTS', $muted);
        foreach (array_values($labels) as $index => $label) {
            $y = 38 + $index * 31;
            $color = $this->color($image, $colors[$index] ?? $defaultColors[$index % count($defaultColors)]);
            imagefilledrectangle($image, 273, $y + 3, 286, $y + 16, $color);
            imagestring($image, 3, 296, $y + 2, $this->label($label, 22), $text);
            imagestring($image, 3, 548, $y + 2, (string)($counts[$index] ?? 0), $muted);
        }
        return $this->imageData($image);
    }

    public function ranking(array $labels, array $counts, string $accent = self::GREEN): string
    {
        $width = 610;
        $height = 260;
        $image = $this->make($width, $height);
        $text = $this->color($image, self::TEXT);
        $muted = $this->color($image, self::MUTED);
        $grid = $this->color($image, '#e8efea');
        $bar = $this->color($image, $accent);
        $count = min(8, count($counts));
        if (!$count || !array_sum($counts)) {
            $this->empty($image, $width, $height);
            return $this->imageData($image);
        }
        $max = max(1, max($counts));
        for ($i = 0; $i < $count; $i++) {
            $y = 16 + $i * 30;
            imagestring($image, 3, 6, $y + 6, $this->label((string)$labels[$i], 22), $text);
            imagefilledrectangle($image, 220, $y + 4, 552, $y + 20, $grid);
            $barWidth = (int)round(max(0, (int)$counts[$i]) / $max * 332);
            if ($barWidth > 0) imagefilledrectangle($image, 220, $y + 4, 220 + $barWidth, $y + 20, $bar);
            imagestring($image, 3, 563, $y + 4, (string)$counts[$i], $muted);
        }
        return $this->imageData($image);
    }
}
