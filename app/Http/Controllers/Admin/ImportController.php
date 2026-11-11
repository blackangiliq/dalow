<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportController extends Controller
{
    /** حقول المنتج التي يمكن ربطها بأعمدة الملف، مع كلمات تساعد على التخمين التلقائي */
    public const FIELDS = [
        'name' => ['label' => 'اسم المنتج', 'required' => true, 'hints' => ['اسم', 'المنتج', 'الدواء', 'المادة', 'name', 'product', 'item', 'drug', 'trade']],
        'price' => ['label' => 'سعر البيع', 'required' => true, 'hints' => ['سعر البيع', 'السعر', 'سعر', 'price', 'sell']],
        'scientific_name' => ['label' => 'الاسم العلمي', 'hints' => ['علمي', 'العلمي', 'الفعالة', 'scientific', 'generic', 'active']],
        'company' => ['label' => 'الشركة المصنعة', 'hints' => ['شركة', 'الشركة', 'المنشأ', 'company', 'manufacturer', 'brand', 'origin']],
        'category' => ['label' => 'القسم', 'hints' => ['قسم', 'القسم', 'صنف', 'تصنيف', 'category', 'group']],
        'barcode' => ['label' => 'الباركود', 'hints' => ['باركود', 'الباركود', 'رمز', 'barcode', 'code', 'sku']],
        'stock' => ['label' => 'الكمية المتوفرة', 'hints' => ['كمية', 'الكمية', 'رصيد', 'مخزون', 'عدد', 'stock', 'qty', 'quantity']],
        'old_price' => ['label' => 'السعر قبل الخصم', 'hints' => ['قبل', 'القديم', 'old', 'before', 'was']],
        'dosage_form' => ['label' => 'الشكل الدوائي', 'hints' => ['شكل', 'الشكل', 'نوع', 'form', 'type']],
        'expires_at' => ['label' => 'تاريخ انتهاء الصلاحية', 'hints' => ['انتهاء', 'الانتهاء', 'صلاحية', 'الصلاحية', 'نفاذ', 'expiry', 'expire', 'exp']],
        'keywords' => ['label' => 'كلمات البحث', 'hints' => ['كلمات', 'keywords', 'tags']],
        'description' => ['label' => 'الوصف', 'hints' => ['وصف', 'الوصف', 'ملاحظات', 'description', 'note']],
        'requires_prescription' => ['label' => 'يحتاج وصفة (نعم/لا)', 'hints' => ['وصفة', 'prescription', 'rx']],
    ];

    public function form()
    {
        return view('admin.import.upload');
    }

    /** الخطوة 1: رفع الملف وعرض أعمدته لاختيار الحقول */
    public function upload(Request $request)
    {
        $request->validate(
            ['file' => 'required|file|max:10240|mimes:xlsx,xls,csv,txt'],
            ['file.required' => 'اختر ملف Excel أو CSV', 'file.mimes' => 'الملف يجب أن يكون بصيغة xlsx أو xls أو csv', 'file.max' => 'حجم الملف أكبر من 10 ميغابايت']
        );
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            return back()->withErrors(['file' => 'الملف يجب أن يكون بصيغة xlsx أو xls أو csv']);
        }

        $this->forget();
        $path = $file->storeAs('imports', Str::uuid().'.'.$ext, 'local');

        try {
            $rows = $this->read($path);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => 'تعذرت قراءة الملف. تأكد أنه ملف Excel سليم.']);
        }
        if (count($rows) < 1) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => 'الملف فارغ']);
        }

        session(['import' => ['path' => $path, 'name' => $file->getClientOriginalName()]]);

        return redirect()->route('admin.import.map');
    }

    /** الخطوة 2: شاشة ربط الأعمدة بالحقول */
    public function map()
    {
        $import = session('import');
        if (! $import || ! Storage::disk('local')->exists($import['path'])) {
            return redirect()->route('admin.import')->with('error', 'ارفع الملف أولاً');
        }
        $rows = $this->read($import['path']);
        $width = max(array_map('count', array_slice($rows, 0, 50)));
        $headers = array_pad(array_map(fn ($v) => trim((string) $v), $rows[0]), $width, '');

        // تخمين الحقل المناسب لكل عمود من عنوانه
        // الحقول ذات العناوين الأدق أولاً، حتى لا يأخذ «الاسم» عمود «الاسم العلمي» مثلاً
        $guess = [];
        $order = ['scientific_name', 'old_price', 'requires_prescription', 'expires_at', 'keywords', 'barcode', 'category', 'company', 'stock', 'dosage_form', 'name', 'price', 'description'];
        foreach ($order as $field) {
            $meta = self::FIELDS[$field];
            foreach ($headers as $i => $header) {
                if ($header === '' || in_array($i, $guess, true)) {
                    continue;
                }
                foreach ($meta['hints'] as $hint) {
                    if (mb_stripos($header, $hint) !== false) {
                        $guess[$field] = $i;
                        break 2;
                    }
                }
            }
        }

        return view('admin.import.map', [
            'fileName' => $import['name'],
            'headers' => $headers,
            'sample' => array_slice($rows, 0, 6),
            'total' => count($rows),
            'fields' => self::FIELDS,
            'guess' => $guess,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    /** الخطوة 3: تنفيذ الاستيراد دفعة واحدة */
    public function run(Request $request)
    {
        $import = session('import');
        if (! $import || ! Storage::disk('local')->exists($import['path'])) {
            return redirect()->route('admin.import')->with('error', 'انتهت جلسة الاستيراد. ارفع الملف من جديد.');
        }
        $data = $request->validate([
            'map' => 'required|array',
            'map.name' => 'required|integer|min:0',
            'map.price' => 'required|integer|min:0',
            'map.*' => 'nullable|integer|min:0',
            'default_category' => 'nullable|exists:categories,id',
            'match_by' => 'required|in:barcode,name',
            'on_duplicate' => 'required|in:update,skip',
        ], ['map.name.required' => 'اختر العمود الذي يحتوي اسم المنتج', 'map.price.required' => 'اختر العمود الذي يحتوي السعر']);

        $map = array_filter($data['map'], fn ($v) => $v !== null && $v !== '');
        $rows = $this->read($import['path']);
        if ($request->boolean('has_header')) {
            unset($rows[0]);
        }

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'categories' => 0];
        $errors = [];
        $categories = Category::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id])->all();
        $createCategories = $request->boolean('create_categories');
        $cell = fn (array $row, string $field) => isset($map[$field]) ? trim((string) ($row[$map[$field]] ?? '')) : '';

        DB::transaction(function () use ($rows, $map, $data, $cell, $createCategories, &$stats, &$errors, &$categories) {
            foreach ($rows as $index => $row) {
                $line = $index + 1;
                $name = $cell($row, 'name');
                if ($name === '' && trim(implode('', array_map('strval', $row))) === '') {
                    continue; // سطر فارغ
                }
                $price = Util::amount($row[$map['price']] ?? null);
                if ($name === '' || $price === null) {
                    $stats['failed']++;
                    if (count($errors) < 30) {
                        $errors[] = "السطر {$line}: ".($name === '' ? 'اسم المنتج فارغ' : "السعر غير مفهوم «{$cell($row, 'price')}»");
                    }

                    continue;
                }

                $attrs = ['name' => mb_substr($name, 0, 160), 'price' => $price];
                foreach (['scientific_name', 'company', 'barcode', 'dosage_form', 'description', 'keywords'] as $f) {
                    if (isset($map[$f]) && ($v = $cell($row, $f)) !== '') {
                        $attrs[$f] = $f === 'barcode' ? Util::digits($v) : $v;
                    }
                }
                if (isset($map['stock'])) {
                    $attrs['stock'] = Util::amount($row[$map['stock']] ?? null) ?? 0;
                }
                if (isset($map['old_price'])) {
                    $old = Util::amount($row[$map['old_price']] ?? null);
                    $attrs['old_price'] = ($old && $old > $price) ? $old : null;
                }
                if (isset($map['expires_at'])) {
                    $attrs['expires_at'] = self::date($row[$map['expires_at']] ?? null);
                }
                if (isset($map['requires_prescription'])) {
                    $attrs['requires_prescription'] = in_array(mb_strtolower($cell($row, 'requires_prescription')), ['1', 'yes', 'y', 'true', 'نعم', 'اي', 'وصفة', 'rx'], true);
                }

                // القسم: من العمود إن وُجد، وإلا القسم الافتراضي
                $catName = $cell($row, 'category');
                if ($catName !== '') {
                    $key = mb_strtolower($catName);
                    if (! isset($categories[$key]) && $createCategories) {
                        $categories[$key] = Category::create([
                            'name' => $catName, 'slug' => CategoryController::slug($catName), 'sort' => 50,
                        ])->id;
                        $stats['categories']++;
                    }
                    if (isset($categories[$key])) {
                        $attrs['category_id'] = $categories[$key];
                    }
                }

                $existing = null;
                if ($data['match_by'] === 'barcode' && ! empty($attrs['barcode'])) {
                    $existing = Product::where('barcode', $attrs['barcode'])->first();
                }
                $existing ??= Product::where('name', $attrs['name'])->first();

                if ($existing) {
                    if ($data['on_duplicate'] === 'skip') {
                        $stats['skipped']++;
                    } else {
                        $existing->update($attrs);
                        $stats['updated']++;
                    }
                } else {
                    Product::create($attrs + ['category_id' => $data['default_category'] ?? null, 'stock' => 0, 'is_active' => true]);
                    $stats['created']++;
                }
            }
        });

        $this->forget();

        return view('admin.import.result', ['stats' => $stats, 'rowErrors' => $errors, 'fileName' => $import['name']]);
    }

    /** ملف نموذجي يوضح شكل القائمة */
    public function template()
    {
        $sheet = (new Spreadsheet)->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->fromArray([
            ['اسم المنتج', 'الاسم العلمي', 'الشركة', 'القسم', 'الباركود', 'السعر', 'السعر قبل الخصم', 'الكمية', 'الشكل الدوائي', 'يحتاج وصفة', 'تاريخ الانتهاء'],
            ['Panadol Extra 24 Tab', 'Paracetamol + Caffeine', 'GSK', 'أدوية', '5000158100503', 2500, '', 120, 'أقراص', 'لا', '2028-06-30'],
            ['Augmentin 1g 14 Tab', 'Amoxicillin + Clavulanic acid', 'GSK', 'أدوية', '5000158105201', 12500, 14000, 40, 'أقراص', 'نعم', '05/2028'],
            ['Centrum Adults 30 Tab', 'Multivitamin', 'Haleon', 'فيتامينات ومكملات', '', 18000, '', 25, 'أقراص', 'لا', ''],
        ]);
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('E2:E200')->getNumberFormat()->setFormatCode('@');

        return response()->streamDownload(
            fn () => (new Xlsx($sheet->getParent()))->save('php://output'),
            'aldalu-products-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /** يقرأ التاريخ بأي صيغة شائعة: تاريخ Excel رقمياً، 2027-05-31، 31/05/2027، أو 05/2027 (آخر الشهر) */
    public static function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value) && $value > 20000 && $value < 80000) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }
            $text = trim(Util::digits((string) $value));
            if (preg_match('#^(\d{1,2})[/.-](\d{4})$#', $text, $m)) {
                return \Illuminate\Support\Carbon::create((int) $m[2], (int) $m[1], 1)->endOfMonth()->toDateString();
            }
            if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $text, $m)) {
                return \Illuminate\Support\Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->toDateString();
            }

            return \Illuminate\Support\Carbon::parse($text)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<int, array<int, mixed>> */
    private function read(string $path): array
    {
        $full = Storage::disk('local')->path($path);
        $reader = IOFactory::createReaderForFile($full);
        $reader->setReadDataOnly(true);
        $rows = $reader->load($full)->getActiveSheet()->toArray(null, true, false, false);

        // حذف الأسطر الفارغة في نهاية الملف
        while ($rows && trim(implode('', array_map('strval', end($rows)))) === '') {
            array_pop($rows);
        }

        return array_values($rows);
    }

    private function forget(): void
    {
        if ($old = session('import.path')) {
            Storage::disk('local')->delete($old);
        }
        session()->forget('import');
    }
}
