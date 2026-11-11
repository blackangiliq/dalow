<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Ui;
use Illuminate\Http\Request;

/** صفحة «واجهة الموقع»: كل ما يظهر للزبون يُدار من هنا */
class AppearanceController extends Controller
{
    public function edit()
    {
        $custom = Ui::customColors();

        return view('admin.appearance', [
            'groups' => Ui::TEXTS,
            'texts' => Ui::texts(),
            'features' => Ui::FEATURES,
            'sections' => Ui::sections(),
            'sectionNames' => Ui::SECTIONS,
            'colors' => collect(Ui::COLORS)->map(fn ($def, $key) => ['label' => $def[0], 'default' => $def[1], 'value' => $custom[$key] ?? $def[1]]),
        ]);
    }

    public function update(Request $request)
    {
        $definitions = Ui::textDefinitions();
        $rules = ['section_order' => 'required|array', 'section_order.*' => 'integer|min:0|max:99'];
        foreach ($definitions as $key => $def) {
            $rules["text.{$key}"] = 'nullable|string|max:'.(($def[2] ?? false) ? 400 : 120);
        }
        foreach (array_keys(Ui::COLORS) as $key) {
            $rules["color.{$key}"] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }
        $data = $request->validate($rules, [], ['text.*' => 'النص', 'color.*' => 'اللون']);

        // النص المطابق للافتراضي لا يُحفظ، ليبقى تابعاً لأي تحسين لاحق في الافتراضي
        foreach ($definitions as $key => $def) {
            $value = trim((string) ($data['text'][$key] ?? ''));
            Setting::put('ui_'.$key, $value === '' || $value === Ui::defaultFor($key) || $value === $def[1] ? null : $value);
        }
        foreach (array_merge(...array_values(Ui::FEATURES)) as $key => $def) {
            Setting::put('on_'.$key, $request->boolean("on.{$key}") ? '1' : '0');
        }
        foreach (Ui::COLORS as $key => [, $default]) {
            $value = strtolower($data['color'][$key]);
            Setting::put('color_'.$key, $value === $default ? null : $value);
        }

        // أقسام الرئيسية: تُرتَّب بالأرقام التي كتبها المدير
        $order = collect(array_keys(Ui::SECTIONS))
            ->map(fn ($key, $i) => ['key' => $key, 'on' => $request->boolean("section_on.{$key}"), 'sort' => (int) ($data['section_order'][$key] ?? 50), 'i' => $i])
            ->sortBy([['sort', 'asc'], ['i', 'asc']])
            ->map(fn ($s) => ['key' => $s['key'], 'on' => $s['on']])->values()->all();
        Setting::put('home_sections', json_encode($order));

        return back()->with('ok', 'حُفظت واجهة الموقع. افتح المتجر لترى التغيير.');
    }

    public function reset(Request $request)
    {
        // مفاتيح صريحة بدل LIKE، ليعمل الحذف بنفس الشكل على SQLite و MySQL
        $keys = match ($request->input('what')) {
            'texts' => array_map(fn ($k) => 'ui_'.$k, array_keys(Ui::textDefinitions())),
            'colors' => array_map(fn ($k) => 'color_'.$k, array_keys(Ui::COLORS)),
            'sections' => ['home_sections'],
            default => [],
        };
        Setting::query()->whereIn('key', $keys)->delete();
        Setting::flushCache();

        return back()->with('ok', 'رجعت القيم الافتراضية');
    }
}
