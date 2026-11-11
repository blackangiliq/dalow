<?php

namespace App\Support;

use App\Models\HealthProfile;
use App\Models\Product;
use App\Search\ProductSearch;

/**
 * يقارن الدواء بحساسيات الشخص المسجلة في ملفه الصحي.
 *
 * الحساسية تُكتب غالباً باسم مجموعة دوائية (بنسلين، سلفا، مضادات الالتهاب)، والدواء يُباع باسم تجاري
 * ومادة فعالة مختلفة، لذلك نوسّع كل حساسية إلى المواد الفعالة المعروفة في مجموعتها، ثم نطابقها
 * مع اسم المنتج ومادته الفعالة وكلماته المفتاحية (بالعربي أو الإنجليزي).
 *
 * هذا تنبيه مساعد للصيدلاني والزبون، وليس بديلاً عن مراجعة الصيدلاني.
 */
class AllergyCheck
{
    /** المجموعات الدوائية الشائعة في الحساسية: كلمات يكتبها الزبون => مواد فعالة وأسماء تجارية تنتمي إليها */
    public const CLASSES = [
        'penicillin' => [
            'names' => ['بنسلين', 'بنسيلين', 'penicillin', 'penicilin', 'بنسلينات', 'اموكسيسيلين', 'اوكمنتين', 'amoxicillin'],
            'label' => 'البنسلين ومشتقاته',
            'drugs' => ['penicillin', 'amoxicillin', 'amoxycillin', 'ampicillin', 'augmentin', 'amoxil', 'julmentin', 'clavulanic', 'flucloxacillin', 'cloxacillin', 'piperacillin', 'benzathine', 'unasyn', 'سلباكتام'],
        ],
        'cephalosporin' => [
            'names' => ['سيفالوسبورين', 'cephalosporin', 'سيفالوسبورينات'],
            'label' => 'السيفالوسبورينات',
            'drugs' => ['cefuroxime', 'ceftriaxone', 'cefixime', 'cefalexin', 'cephalexin', 'cefadroxil', 'cefotaxime', 'cefdinir', 'cefprozil', 'zinnat', 'rocephin', 'suprax'],
        ],
        'sulfa' => [
            'names' => ['سلفا', 'sulfa', 'سلفوناميد', 'sulfonamide', 'sulpha'],
            'label' => 'السلفا',
            'drugs' => ['sulfamethoxazole', 'trimethoprim', 'septrin', 'bactrim', 'sulfasalazine', 'sulfadiazine'],
        ],
        'nsaid' => [
            'names' => ['مضادات الالتهاب', 'مسكنات الالتهاب', 'nsaid', 'nsaids', 'اسبرين', 'أسبرين', 'aspirin', 'ايبوبروفين', 'ibuprofen', 'بروفين', 'فولتارين', 'ديكلوفيناك'],
            'label' => 'مضادات الالتهاب غير الستيرويدية والأسبرين',
            'drugs' => ['aspirin', 'acetylsalicylic', 'ibuprofen', 'brufen', 'profinal', 'nurofen', 'diclofenac', 'voltaren', 'naproxen', 'mefenamic', 'ponstan', 'ketoprofen', 'meloxicam', 'piroxicam', 'celecoxib', 'indomethacin', 'ketorolac'],
        ],
        'macrolide' => [
            'names' => ['ماكروليد', 'macrolide', 'ازيثرومايسين', 'زيثروماكس', 'azithromycin', 'كلاريثرومايسين'],
            'label' => 'الماكروليدات',
            'drugs' => ['azithromycin', 'zithromax', 'azi', 'clarithromycin', 'klacid', 'erythromycin'],
        ],
        'quinolone' => [
            'names' => ['كينولون', 'quinolone', 'سيبرو', 'cipro', 'ciprofloxacin', 'ليفوفلوكساسين'],
            'label' => 'الكينولونات',
            'drugs' => ['ciprofloxacin', 'cipro', 'levofloxacin', 'tavanic', 'moxifloxacin', 'ofloxacin', 'norfloxacin'],
        ],
        'tetracycline' => [
            'names' => ['تتراسايكلين', 'tetracycline', 'دوكسي', 'doxycycline'],
            'label' => 'التتراسايكلينات',
            'drugs' => ['tetracycline', 'doxycycline', 'minocycline', 'vibramycin'],
        ],
        'paracetamol' => [
            'names' => ['باراسيتامول', 'paracetamol', 'acetaminophen', 'بنادول', 'panadol', 'ادول'],
            'label' => 'الباراسيتامول',
            'drugs' => ['paracetamol', 'acetaminophen', 'panadol', 'paracetol', 'adol', 'revanin', 'tylenol'],
        ],
        'codeine' => [
            'names' => ['كودين', 'codeine', 'افيونات', 'مورفين', 'opioid', 'tramadol', 'ترامادول'],
            'label' => 'الكودين والأفيونات',
            'drugs' => ['codeine', 'tramadol', 'morphine', 'dextromethorphan', 'pethidine', 'fentanyl', 'oxycodone'],
        ],
        'metronidazole' => [
            'names' => ['ميترونيدازول', 'metronidazole', 'فلاجيل', 'flagyl'],
            'label' => 'الميترونيدازول',
            'drugs' => ['metronidazole', 'flagyl', 'tinidazole'],
        ],
        'iodine' => [
            'names' => ['يود', 'اليود', 'iodine', 'بيتادين', 'betadine'],
            'label' => 'اليود',
            'drugs' => ['iodine', 'povidone', 'betadine'],
        ],
        'latex' => [
            'names' => ['لاتكس', 'latex', 'مطاط'],
            'label' => 'اللاتكس (المطاط)',
            'drugs' => ['latex'],
        ],
        'lactose' => [
            'names' => ['لاكتوز', 'lactose', 'حليب البقر', 'بروتين الحليب'],
            'label' => 'اللاكتوز وبروتين الحليب',
            'drugs' => ['lactose', 'aptamil', 'novalac', 'milk'],
        ],
    ];

    /** اختيارات سريعة تُعرض للزبون عند تعبئة ملفه */
    public const COMMON = ['البنسلين', 'الأسبرين ومضادات الالتهاب', 'السلفا', 'السيفالوسبورين', 'الكودين', 'الباراسيتامول', 'اليود', 'اللاتكس', 'اللاكتوز'];

    /**
     * @return array<int, array{allergy: \App\Models\Allergy, via: string}> الحساسيات التي يتعارض معها المنتج
     */
    public static function conflicts(?HealthProfile $profile, Product $product): array
    {
        if (! $profile || ! Ui::on('allergy_alerts')) {
            return [];
        }
        $productWords = self::words($product->name.' '.$product->scientific_name.' '.$product->keywords);
        if (! $productWords) {
            return [];
        }

        $hits = [];
        foreach ($profile->allergies as $allergy) {
            [$terms, $label] = self::expand($allergy->substance);
            foreach ($terms as $term) {
                if (self::matches($term, $productWords)) {
                    $hits[] = ['allergy' => $allergy, 'via' => $label];
                    break;
                }
            }
        }

        return $hits;
    }

    /**
     * يوسّع ما كتبه الزبون: اسم المادة نفسه، ومواد مجموعتها الدوائية إن عرفناها.
     *
     * @return array{0: string[], 1: ?string} الكلمات، واسم المجموعة إن طابقت
     */
    public static function expand(string $substance): array
    {
        $own = self::words($substance);
        $terms = $own;
        $label = null;
        $text = ProductSearch::normalize($substance);
        foreach (self::CLASSES as $class) {
            foreach ($class['names'] as $name) {
                $n = ProductSearch::normalize($name);
                if ($n !== '' && (str_contains($text, $n) || (mb_strlen($text) >= 4 && str_contains($n, $text)))) {
                    $terms = [...$terms, ...array_map(fn ($d) => ProductSearch::normalize($d), $class['drugs'])];
                    $label ??= $class['label'];
                    break;
                }
            }
        }

        return [array_values(array_unique(array_filter($terms, fn ($t) => mb_strlen($t) >= 3))), $label];
    }

    /** @param string[] $productWords */
    private static function matches(string $term, array $productWords): bool
    {
        $sk = ProductSearch::skeleton($term);
        foreach ($productWords as $word) {
            if ($word === $term || (mb_strlen($term) >= 5 && str_starts_with($word, $term))) {
                return true;
            }
            // بالعربي أو الإنجليزي: أموكسيسيلين = amoxicillin
            if (strlen($sk) >= 4 && ProductSearch::skeleton($word) === $sk) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    private static function words(string $text): array
    {
        return array_values(array_filter(ProductSearch::tokens($text), fn ($w) => mb_strlen($w) >= 3 && ! preg_match('/^\d/', $w)));
    }
}
