<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->search($request->query('q'));
        if ($request->filled('category')) {
            $query->where('category_id', $request->query('category'));
        }
        if ($request->query('stock') === 'low') {
            $query->where('stock', '<=', 5);
        }

        return view('admin.products.index', [
            'products' => $query->latest('id')->paginate(30)->withQueryString(),
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['is_active' => true, 'stock' => 0]), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $product = Product::create($this->data($request));
        $this->saveImage($request, $product);

        return redirect()->route('admin.products.index')->with('ok', 'أضيف المنتج «'.$product->name.'»');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('sort')->get(), 'waiting' => $product->favoredBy()->count()]);
    }

    public function update(Request $request, Product $product)
    {
        $before = $product->price;
        $product->update($this->data($request));
        $this->saveImage($request, $product);

        // نزل السعر وهناك من طلب التنبيه: نعرض للمدير أرقامهم ليذكّرهم
        $waiting = $product->favoredBy()->count();
        if ($product->price < $before && $waiting) {
            return redirect()->route('admin.price-alerts', ['product' => $product->id])
                ->with('ok', "نزل سعر «{$product->name}» وعليه {$waiting} طلب تنبيه. وصلهم إشعار في حساباتهم، وهذه أرقامهم لتذكيرهم.");
        }

        return redirect()->route('admin.products.index')->with('ok', 'حُفظت تعديلات «'.$product->name.'»');
    }

    public function destroy(Product $product)
    {
        $this->deleteImage($product);
        $product->delete();

        return back()->with('ok', 'حُذف المنتج');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1', 'ids.*' => 'integer',
            'action' => 'required|in:delete,activate,deactivate',
        ], ['ids.required' => 'حدد منتجاً واحداً على الأقل']);

        $query = Product::whereIn('id', $data['ids']);
        if ($data['action'] === 'delete') {
            $query->get()->each(function (Product $p) {
                $this->deleteImage($p);
                $p->delete();
            });
        } else {
            $query->update(['is_active' => $data['action'] === 'activate']);
        }

        return back()->with('ok', 'تم تنفيذ الإجراء على '.count($data['ids']).' منتج');
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'scientific_name' => 'nullable|string|max:160',
            'company' => 'nullable|string|max:120',
            'barcode' => 'nullable|string|max:60',
            'keywords' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'dosage_form' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:3000',
            'price' => 'required|integer|min:0|max:100000000',
            'old_price' => 'nullable|integer|min:0|max:100000000',
            'stock' => 'required|integer|min:0|max:1000000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [], ['name' => 'اسم المنتج', 'price' => 'السعر', 'stock' => 'الكمية', 'image' => 'الصورة']);
        unset($data['image']);

        return $data + [
            'requires_prescription' => $request->boolean('requires_prescription'),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function saveImage(Request $request, Product $product): void
    {
        if ($file = $request->file('image')) {
            $this->deleteImage($product);
            $name = $product->id.'-'.Str::random(8).'.'.$file->extension();
            $file->move(public_path('uploads/products'), $name);
            $product->update(['image' => $name]);
        }
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image) {
            @unlink(public_path('uploads/products/'.basename($product->image)));
        }
    }
}
