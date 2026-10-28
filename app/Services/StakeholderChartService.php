<?php

namespace App\Services;

class StakeholderChartService
{
    private const GREEN = '#17563E';
    private const GOLD = '#C69C4C';
    private const INK = '#274735';
    private const MUTED = '#607669';
    private const GRID = '#E5EEE8';

    public function available(): bool
    {
        return extension_loaded('gd');
    }

    private function colour(\GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        return imagecolorallocate(
            $image,
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    private function canvas(int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, $this->colour($image, '#FFFFFF'));
        imageantialias($image, true);
        return $image;
    }

    private function label(string $text, int $max = 36): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        return mb_strimwidth($text, 0, $max, '...');
    }

    private function png(\GdImage $image): string
    {
        ob_start();
        imagepng($image, null, 7);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    /** @param string[] $labels @param int[] $counts */
    public function trend(array $labels, array $counts): ?string
    {
        if (!$this->available()) return null;
        $width = 920;
        $height = 270;
        $image = $this->canvas($width, $height);
        $green = $this->colour($image, self::GREEN);
        $muted = $this->colour($image, self::MUTED);
        $grid = $this->colour($image, self::GRID);
        $left = 56; $right = 18; $top = 26; $bottom = 52;
        $max = max(1, ...array_map('intval', $counts ?: [0]));
        for ($line = 0; $line <= 4; $line++) {
            $y = $top + (int) (($height - $top - $bottom) * $line / 4);
            imageline($image, $left, $y, $width - $right, $y, $grid);
            imagestring($image, 2, 7, $y - 6, (string) round($max * (4 - $line) / 4), $muted);
        }
        $n = max(1, count($counts));
        $last = null;
        foreach ($counts as $i => $value) {
            $x = $left + (int) (($width - $left - $right) * $i / max(1, $n - 1));
            $y = $top + (int) (($height - $top - $bottom) * (1 - (int) $value / $max));
            if ($last) {
                imagesetthickness($image, 4);
                imageline($image, $last[0], $last[1], $x, $y, $green);
                imagesetthickness($image, 1);
            }
            imagefilledellipse($image, $x, $y, 9, 9, $green);
            $last = [$x, $y];
            if ($i % 2 === 0 || $n <= 7) {
                $short = $this->label((string) ($labels[$i] ?? ''), 8);
                imagestring($image, 2, $x - (int) (strlen($short) * 3.5), $height - 37, $short, $muted);
            }
        }
        return $this->png($image);
    }

    /** @param array<string,int> $values */
    public function bars(array $values, string $fill = self::GREEN, int $limit = 8): ?string
    {
        if (!$this->available()) return null;
        $values = array_slice($values, 0, $limit, true);
        $height = max(120, 24 + count($values) * 47);
        $width = 920;
        $image = $this->canvas($width, $height);
        $colour = $this->colour($image, $fill);
        $grid = $this->colour($image, self::GRID);
        $ink = $this->colour($image, self::INK);
        $max = max(1, ...array_map('intval', array_values($values) ?: [0]));
        $start = 290; $area = $width - $start - 80;
        if (!$values) {
            imagestring($image, 4, 18, 48, 'No observations match the selected filters.', $ink);
            return $this->png($image);
        }
        foreach ($values as $label => $number) {
            $i = array_search($label, array_keys($values), true);
            $y = 18 + $i * 47;
            imagestring($image, 4, 13, $y + 9, $this->label((string) $label, 30), $ink);
            imagefilledrectangle($image, $start, $y + 5, $start + $area, $y + 29, $grid);
            if ((int) $number > 0) {
                $len = max(3, (int) ($area * (int) $number / $max));
                imagefilledrectangle($image, $start, $y + 5, $start + $len, $y + 29, $colour);
            }
            imagestring($image, 4, $start + $area + 12, $y + 9, (string) $number, $ink);
        }
        return $this->png($image);
    }
}
