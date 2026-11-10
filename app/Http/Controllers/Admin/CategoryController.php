<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['slug'] = self::slug($data['name']);
        Category::create($data);

        return back()->with('ok', 'أضيف القسم «'.$data['name'].'»');
    }

    public function update(Request $request, int $id)
    {
        Category::findOrFail($id)->update($this->data($request));

        return back()->with('ok', 'حُفظ القسم');
    }

    public function destroy(int $id)
    {
        Category::findOrFail($id)->delete();

        return back()->with('ok', 'حُذف القسم، وبقيت منتجاته بدون قسم');
    }

    public static function slug(string $name): string
    {
        $base = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($name)), '-') ?: Str::random(6);
        $slug = $base;
        for ($i = 2; Category::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:60',
            'icon' => 'required|in:'.implode(',', array_keys(Category::ICONS)),
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => 'nullable|integer|min:0|max:999',
        ], [], ['name' => 'اسم القسم']) ;
    }
}
