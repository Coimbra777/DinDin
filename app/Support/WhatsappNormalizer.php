<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normaliza número para armazenamento (apenas dígitos; BR sem DDI recebe 55).
 */
final class WhatsappNormalizer
{
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '55')) {
            return $digits;
        }

        $len = strlen($digits);
        if ($len >= 10 && $len <= 11) {
            return '55' . $digits;
        }

        return $digits;
    }
}
