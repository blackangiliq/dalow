<?php

namespace App\Services\Telegram;

final class PhoneNumber
{
    /**
     * يحوّل رقماً محلياً أو دولياً إلى صيغة +<رمز الدولة><الرقم> التي يتطلبها Telegram.
     * أمثلة (رمز العراق 964): 07831416883 و 009647831416883 و +964 783 141 6883 => +9647831416883
     */
    public static function toInternational(?string $raw, string $countryCode = '964'): ?string
    {
        $trimmed = trim((string) $raw);
        $digits = preg_replace('/\D+/', '', $trimmed);

        if ($digits === '') {
            return null;
        }

        if (! str_starts_with($trimmed, '+')) {
            $digits = match (true) {
                str_starts_with($digits, '00') => substr($digits, 2),
                str_starts_with($digits, $countryCode) => $digits,
                str_starts_with($digits, '0') => $countryCode.substr($digits, 1),
                default => $countryCode.$digits,
            };
        }

        $length = strlen($digits);

        return $length >= 10 && $length <= 15 ? '+'.$digits : null;
    }
}
