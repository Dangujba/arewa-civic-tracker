<?php
namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Quill's supported markup only. Legacy Android/plain descriptions stay plain
 * and are escaped on display. Never render arbitrary submitted HTML directly.
 */
final class CaseRichText
{
    public static function isFormatted(?string $value): bool
    {
        return is_string($value) && preg_match(
            '~</?(?:p|br|strong|b|em|i|u|s|ul|ol|li|blockquote|h2|h3|a)(?:\s|/?>)~i',
            $value
        ) === 1;
    }

    private static function purifier(): HTMLPurifier
    {
        static $purifier = null;
        if ($purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ul,ol,li,blockquote,h2,h3,a[href|title]');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('Cache.DefinitionImpl', null);
            $purifier = new HTMLPurifier($config);
        }
        return $purifier;
    }

    public static function clean(?string $value): string
    {
        $value = trim((string) $value);
        return self::isFormatted($value) ? self::purifier()->purify($value) : $value;
    }

    public static function plainText(?string $value): string
    {
        $value = (string) $value;
        if (self::isFormatted($value)) {
            $value = preg_replace('~</?(?:p|li|h2|h3|blockquote|br|div)\b[^>]*>~i', "\n", $value);
            $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return trim(preg_replace('/[\x{00A0}\s]+/u', ' ', $value) ?? $value);
    }

    public static function render(?string $value): string
    {
        $clean = self::clean($value);
        return self::isFormatted($clean) ? $clean : nl2br(e($clean));
    }
}
