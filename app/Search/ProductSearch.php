<?php

namespace App\Search;

use App\Models\Product;
use App\Models\SearchSynonym;
use App\Support\Util;
use Illuminate\Support\Facades\Cache;

/**
 * محرك بحث المنتجات.
 *
 * يجد المنتج حتى لو كتبه الزبون بالعربية والاسم في القائمة بالإنجليزية (بنادول => Panadol)،
 * أو أخطأ في حرف (Panadool)، أو كتب العرَض بدل الدواء (صداع => مسكنات) عبر المرادفات.
 *
 * طريقة العمل: كل كلمة تُحوَّل إلى «هيكل صوتي» من الحروف الساكنة بعد توحيد الحروف المتشابهة
 * بين العربية واللاتينية، ثم تُقارن الكلمات نصاً، فصوتاً، فبمسافة التحرير (Levenshtein).
 */
class ProductSearch
{
    private const CACHE = 'search.index.v1';

    /** درجات المطابقة: كلما كانت المطابقة أدق كانت الدرجة أعلى */
    private const EXACT = 100;
    private const PREFIX = 85;
    private const CONTAINS = 65;
    private const PHONETIC = 75;
    private const PHONETIC_PREFIX = 58;
    private const FUZZY = 48;
    private const PHONETIC_FUZZY = 45;

    public static function forget(): void
    {
        Cache::forget(self::CACHE);
    }

    /**
     * @return array{ids: int[], via: string, corrected: ?string, expanded: string[]}
     */
    public function search(string $query, int $limit = 300): array
    {
        $empty = ['ids' => [], 'via' => 'none', 'corrected' => null, 'expanded' => []];
        $tokens = self::tokens($query);
        if (! $tokens) {
            return $empty;
        }
        $index = $this->index();

        // باركود كامل: نتيجة واحدة مؤكدة
        $digits = preg_replace('/\D/', '', Util::digits($query));
        if (strlen($digits) >= 6 && isset($index['barcodes'][$digits])) {
            return ['ids' => [$index['barcodes'][$digits]], 'via' => 'barcode', 'corrected' => null, 'expanded' => []];
        }

        // كل كلمة في البحث «خانة» لها بدائل: الكلمة نفسها ومرادفاتها
        [$slots, $expanded] = $this->slots($tokens, self::normalize($query), $index['synonyms']);

        $full = [];
        $partial = [];
        foreach ($index['products'] as $id => $p) {
            $sum = 0;
            $matched = 0;
            $loosest = 0;   // 0 نصي، 1 مرادف، 2 صوتي، 3 تصحيح إملائي
            $word = null;
            foreach ($slots as $alternatives) {
                $best = 0;
                $kind = 0;
                $hit = null;
                foreach ($alternatives as [$token, $weight, $isSynonym]) {
                    [$score, $matchedWord, $matchKind] = $this->match($token, $p);
                    $score = (int) round($score * $weight);
                    if ($score > $best) {
                        $best = $score;
                        $hit = $matchedWord;
                        $kind = max($matchKind, $isSynonym ? 1 : 0);
                    }
                }
                if ($best > 0) {
                    $matched++;
                    $sum += $best;
                    if ($kind > $loosest) {
                        $loosest = $kind;
                        $word = $hit;
                    }
                }
            }
            if (! $matched) {
                continue;
            }
            $bonus = ($p['stock'] ? 6 : 0) + ($p['feat'] ? 3 : 0) + ($p['off'] ? 2 : 0);
            if ($matched === count($slots)) {
                $full[$id] = [$sum + $bonus, $loosest, $word];
            } else {
                $partial[$id] = [(int) ($sum / 2) + $bonus, $loosest, $word];
            }
        }

        $pool = $full ?: $partial;
        if (! $pool) {
            return $empty + ['expanded' => $expanded];
        }
        uasort($pool, fn ($a, $b) => $b[0] <=> $a[0]);
        $ids = array_slice(array_keys($pool), 0, $limit);
        [, $loosest, $word] = $pool[$ids[0]];

        $via = match (true) {
            ! $full => 'partial',
            $loosest === 1 => 'synonym',
            $loosest === 2 => 'phonetic',
            $loosest === 3 => 'fuzzy',
            default => 'exact',
        };

        return [
            'ids' => $ids,
            'via' => $via,
            // عند المطابقة الصوتية أو تصحيح خطأ إملائي نخبر الزبون بالكلمة التي وجدناها
            'corrected' => in_array($via, ['phonetic', 'fuzzy'], true) ? $word : null,
            'expanded' => $expanded,
        ];
    }

    /** @return array{0: int, 1: ?string, 2: int} الدرجة، الكلمة التي طابقت، ونوع المطابقة (0 نصي، 2 صوتي، 3 إملائي) */
    private function match(string $token, array $p): array
    {
        $best = 0;
        $hit = null;
        $len = mb_strlen($token);

        foreach ([['n', 0], ['s', 20], ['o', 40]] as [$field, $penalty]) {
            foreach ($p[$field] as $word) {
                $score = match (true) {
                    $word === $token => self::EXACT,
                    $len >= 2 && str_starts_with($word, $token) => self::PREFIX,
                    // حرف واحد (مثل د في «فيتامين د») يطابق الكلمات القصيرة فقط مثل d3
                    $len === 1 && mb_strlen($word) <= 3 && str_starts_with($word, $token) => self::PREFIX,
                    $len >= 3 && str_contains($word, $token) => self::CONTAINS,
                    default => 0,
                };
                if ($score - $penalty > $best) {
                    $best = $score - $penalty;
                    $hit = $word;
                }
            }
        }
        if ($best >= self::CONTAINS) {
            return [$best, $hit, 0];
        }
        $kind = 0;

        // مطابقة صوتية: تجمع العربية واللاتينية في هيكل واحد
        $sk = self::skeleton($token);
        $skLen = strlen($sk);
        if ($skLen >= 1) {
            foreach ($p['k'] as $key => $word) {
                $score = match (true) {
                    // هيكل من حرفين لا يكفي دليلاً إلا إذا كانت الكلمة نفسها قصيرة
                    $key === $sk => $skLen >= 3 ? self::PHONETIC : (($skLen === 2 && $len <= 4) || ($len === 1 && mb_strlen($word) <= 3) ? 52 : 0),
                    $skLen >= 3 && str_starts_with($key, $sk) => self::PHONETIC_PREFIX,
                    $skLen >= 5 && abs(strlen($key) - $skLen) <= 1 && levenshtein($key, $sk) <= 1 => self::PHONETIC_FUZZY,
                    default => 0,
                };
                if ($score > $best) {
                    $best = $score;
                    $hit = $word;
                    $kind = $word === $token ? 0 : 2;
                }
            }
        }
        if ($best >= self::PHONETIC_FUZZY) {
            return [$best, $hit, $kind];
        }

        // خطأ إملائي بنفس اللغة: حرف واحد للكلمات المتوسطة، حرفان للطويلة
        if ($len >= 4 && ! preg_match('/\d/', $token)) {
            $max = $len >= 8 ? 2 : 1;
            foreach ([...$p['n'], ...$p['s']] as $word) {
                $wl = mb_strlen($word);
                if (abs($wl - $len) > $max || $wl < 4) {
                    continue;
                }
                if (self::FUZZY > $best && self::distance($word, $token) <= $max) {
                    $best = self::FUZZY;
                    $hit = $word;
                    $kind = 3;
                }
            }
        }

        return [$best, $hit, $kind];
    }

    /**
     * @param  string[]  $tokens
     * @return array{0: array<int, array<int, array{0: string, 1: float, 2: bool}>>, 1: string[]}
     */
    private function slots(array $tokens, string $whole, array $synonyms): array
    {
        $expanded = [];
        // مرادف لعبارة كاملة (مثل «مضاد حيوي»): خانة واحدة بدائلها الكلمات الموسَّعة
        if (count($tokens) > 1 && isset($synonyms[$whole])) {
            $alts = [];
            foreach ($synonyms[$whole] as $extra) {
                $alts[] = [$extra, 0.9, true];
                $expanded[] = $extra;
            }

            return [[$alts], $expanded];
        }

        $slots = [];
        foreach ($tokens as $token) {
            $alts = [[$token, 1.0, false]];
            foreach ($synonyms[$token] ?? [] as $extra) {
                $alts[] = [$extra, 0.9, true];
                $expanded[] = $extra;
            }
            $slots[] = $alts;
        }

        return [$slots, array_values(array_unique($expanded))];
    }

    /** فهرس البحث: يُبنى مرة ويُحفظ، ويُمسح عند أي تعديل على المنتجات أو المرادفات */
    private function index(): array
    {
        return Cache::rememberForever(self::CACHE, function () {
            $products = [];
            $barcodes = [];
            foreach (Product::active()->with('category:id,name')->get() as $p) {
                $name = self::tokens($p->name.' '.$p->keywords);
                $sci = self::tokens((string) $p->scientific_name);
                $other = self::tokens($p->company.' '.($p->category->name ?? '').' '.$p->dosage_form);
                $keys = [];
                foreach ([...$name, ...$sci] as $word) {
                    $sk = self::skeleton($word);
                    if ($sk !== '') {
                        $keys[$sk] ??= $word;
                    }
                }
                $products[$p->id] = [
                    'n' => $name, 's' => $sci, 'o' => $other, 'k' => $keys,
                    'stock' => $p->stock > 0, 'feat' => $p->is_featured, 'off' => $p->discount > 0,
                ];
                if ($p->barcode) {
                    $barcodes[preg_replace('/\D/', '', $p->barcode)] = $p->id;
                }
            }

            $synonyms = [];
            foreach (SearchSynonym::all() as $s) {
                $extra = [];
                foreach (preg_split('/[،,]+/u', $s->expands_to) as $part) {
                    $extra = [...$extra, ...self::tokens($part)];
                }
                foreach (preg_split('/[،,]+/u', $s->term) as $term) {
                    $key = self::normalize($term);
                    if ($key !== '') {
                        $synonyms[$key] = array_values(array_unique([...($synonyms[$key] ?? []), ...$extra]));
                    }
                }
            }

            return compact('products', 'barcodes', 'synonyms');
        });
    }

    /** توحيد النص: أرقام لاتينية، حروف صغيرة، وتوحيد أشكال الألف والياء والتاء المربوطة وحذف التشكيل */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(Util::digits($text));
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ة' => 'ه', 'گ' => 'ك', 'ڤ' => 'ف', 'پ' => 'ب', 'چ' => 'ج']);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** @return string[] */
    public static function tokens(string $text): array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }
        $tokens = [];
        foreach (explode(' ', $normalized) as $word) {
            // «ال» التعريف لا تفرّق في المعنى
            if (mb_strlen($word) > 4 && str_starts_with($word, 'ال')) {
                $word = mb_substr($word, 2);
            }
            $tokens[$word] = true;
        }

        return array_map('strval', array_keys($tokens));
    }

    /**
     * الهيكل الصوتي: الحروف الساكنة فقط بعد توحيد المتشابه نطقاً بين العربية واللاتينية.
     * بنادول و Panadol => bndl ، اوكمنتين و Augmentin => kmntn ، فولتارين و Voltaren => fltrn
     */
    public static function skeleton(string $word): string
    {
        if (preg_match('/^\d/', $word)) {
            return '';
        }
        if (preg_match('/\p{Arabic}/u', $word)) {
            $out = strtr($word, [
                'ب' => 'b', 'ت' => 't', 'ط' => 't', 'ث' => 't', 'ج' => 'k', 'ح' => '', 'خ' => 'k', 'د' => 'd', 'ض' => 'd',
                'ذ' => 'z', 'ر' => 'r', 'ز' => 'z', 'س' => 's', 'ص' => 's', 'ش' => 's', 'ع' => '', 'غ' => 'k', 'ف' => 'f',
                'ق' => 'k', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => '', 'و' => '', 'ي' => '', 'ا' => '', 'ء' => '',
            ]);
            $out = preg_replace('/[^a-z]/', '', $out);
        } else {
            $out = strtolower($word);
            $out = str_replace(['ph', 'sh', 'ch', 'ck', 'th', 'x'], ['f', 's', 'k', 'k', 't', 'ks'], $out);
            $out = preg_replace('/c(?=[eiy])/', 's', $out);
            $out = strtr($out, ['c' => 'k', 'q' => 'k', 'g' => 'k', 'j' => 'k', 'p' => 'b', 'v' => 'f']);
            $out = preg_replace('/[^bdfklmnrstz]/', '', $out);
        }

        return preg_replace('/(.)\1+/', '$1', $out);
    }

    /** مسافة التحرير مع دعم الحروف العربية (levenshtein الأصلية تعمل على البايتات) */
    private static function distance(string $a, string $b): int
    {
        if (! preg_match('/[^\x00-\x7F]/', $a.$b)) {
            return levenshtein($a, $b);
        }
        $map = [];
        $encode = function (string $s) use (&$map) {
            $out = '';
            foreach (mb_str_split($s) as $ch) {
                $map[$ch] ??= chr(33 + count($map) % 90);
                $out .= $map[$ch];
            }

            return $out;
        };

        return levenshtein($encode($a), $encode($b));
    }
}
