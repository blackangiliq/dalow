<?php

namespace App\Support;

use App\Models\Setting;

class Util
{
    /** تحويل الأرقام العربية والفارسية إلى لاتينية */
    public static function digits(?string $value): string
    {
        return strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    /** توحيد رقم الهاتف العراقي إلى الصيغة 07XXXXXXXXX */
    public static function phone(?string $value): string
    {
        $d = preg_replace('/\D+/', '', self::digits($value));
        if (strlen($d) >= 10) {
            $last = substr($d, -10);
            if ($last[0] === '7') {
                return '0'.$last;
            }
        }

        return $d;
    }

    public static function validPhone(string $normalized): bool
    {
        return (bool) preg_match('/^07[3-9]\d{8}$/', $normalized);
    }

    /** قراءة مبلغ من نص مثل "12,500 د.ع" أو "١٢٥٠٠" */
    public static function amount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? (int) round($value) : null;
        }
        $s = str_replace([',', '٬', ' '], '', self::digits((string) $value));
        if (! preg_match('/\d+(\.\d+)?/', $s, $m)) {
            return null;
        }

        return (int) round((float) $m[0]);
    }

    /** رابط محادثة واتساب مع رسالة جاهزة. يقبل الرقم المحلي 07XX أو الدولي 9647XX */
    public static function wa(?string $number, string $text = ''): string
    {
        $d = preg_replace('/\D+/', '', self::digits($number));
        if (str_starts_with($d, '07') && strlen($d) === 11) {
            $d = '964'.substr($d, 1);
        }

        return 'https://wa.me/'.$d.($text !== '' ? '?text='.rawurlencode($text) : '');
    }

    /** رقم واتساب الصيدلانية للاستشارات، أو رقم الطلبات إن لم يُحدَّد */
    public static function pharmacistNumber(): string
    {
        return (string) (Setting::get('whatsapp_pharmacist') ?: Setting::get('whatsapp'));
    }

    /** رابط «استشر الصيدلانية» مع ذكر أن الزبون قادم من الموقع ومن أي صفحة أو منتج */
    public static function consultLink(?\App\Models\Product $product = null, ?string $pageUrl = null): string
    {
        $site = Setting::get('name');
        $label = Setting::get('pharmacist_label');
        if ($product) {
            $text = "مرحباً، أنا قادم من موقع {$site} وأريد استشارة {$label} عن هذا المنتج:\n"
                ."{$product->name}\n".route('product', $product)."\n\nسؤالي: ";
        } else {
            $text = "مرحباً، أنا قادم من موقع {$site} وأريد استشارة {$label}."
                .($pageUrl ? "\nالصفحة: {$pageUrl}" : '')."\n\nسؤالي: ";
        }

        return self::wa(self::pharmacistNumber(), $text);
    }

    public static function money(int|float|null $amount): string
    {
        return number_format((int) $amount).' د.ع';
    }

    /** @return array<string,int> اسم المنطقة => أجور التوصيل */
    public static function areas(): array
    {
        $areas = [];
        foreach (preg_split('/\r?\n/', (string) Setting::get('delivery_areas')) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if ($parts[0] !== '') {
                $areas[$parts[0]] = self::amount($parts[1] ?? 0) ?? 0;
            }
        }

        return $areas;
    }

    public static function deliveryFee(string $area, int $subtotal): int
    {
        $free = self::amount(Setting::get('free_delivery_over'));
        if ($free && $subtotal >= $free) {
            return 0;
        }

        return self::areas()[$area] ?? 0;
    }
}
