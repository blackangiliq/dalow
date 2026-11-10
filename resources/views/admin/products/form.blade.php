@extends('layouts.admin')
@section('title', $product->exists ? 'تعديل '.$product->name : 'منتج جديد')

@section('content')
<div class="head">
    <div><h1>{{ $product->exists ? 'تعديل المنتج' : 'منتج جديد' }}</h1></div>
    <a class="btn btn--ghost" href="{{ route('admin.products.index') }}">رجوع للمنتجات</a>
</div>

<form class="card" method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if ($product->exists) @method('put') @endif
    <div class="form">
        <div class="field">
            <label for="name">اسم المنتج</label>
            <input id="name" type="text" name="name" value="{{ old('name', $product->name) }}" required>
        </div>
        <div class="field">
            <label for="scientific_name">الاسم العلمي (المادة الفعالة)</label>
            <input id="scientific_name" type="text" name="scientific_name" dir="ltr" value="{{ old('scientific_name', $product->scientific_name) }}">
            <small>يظهر به المنتج كبديل للأدوية التي لها نفس المادة</small>
        </div>
        <div class="field">
            <label for="company">الشركة المصنعة</label>
            <input id="company" type="text" name="company" value="{{ old('company', $product->company) }}">
        </div>
        <div class="field">
            <label for="barcode">الباركود</label>
            <input id="barcode" type="text" name="barcode" dir="ltr" value="{{ old('barcode', $product->barcode) }}">
        </div>
        <div class="field">
            <label for="category_id">القسم</label>
            <select id="category_id" name="category_id">
                <option value="">بدون قسم</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="dosage_form">الشكل الدوائي</label>
            <select id="dosage_form" name="dosage_form">
                <option value="">غير محدد</option>
                @foreach (array_keys(\App\Models\Product::FORMS) as $f)<option @selected(old('dosage_form', $product->dosage_form) === $f)>{{ $f }}</option>@endforeach
            </select>
        </div>
        <div class="field">
            <label for="price">سعر البيع (د.ع)</label>
            <input id="price" type="number" name="price" min="0" step="250" value="{{ old('price', $product->price) }}" required>
        </div>
        <div class="field">
            <label for="old_price">السعر قبل الخصم (د.ع)</label>
            <input id="old_price" type="number" name="old_price" min="0" step="250" value="{{ old('old_price', $product->old_price) }}">
            <small>اتركه فارغاً إذا لا يوجد خصم</small>
        </div>
        <div class="field">
            <label for="stock">الكمية المتوفرة</label>
            <input id="stock" type="number" name="stock" min="0" value="{{ old('stock', $product->stock) }}" required>
        </div>
        <div class="field">
            <label for="image">صورة المنتج</label>
            <input id="image" type="file" name="image" accept="image/*">
            <small>اختياري. بدون صورة يظهر رمز يناسب الشكل الدوائي.</small>
        </div>
        <div class="field field--full">
            <label for="description">الوصف</label>
            <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="field field--full checks">
            <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> ظاهر في المتجر</label>
            <label class="check"><input type="checkbox" name="requires_prescription" value="1" @checked(old('requires_prescription', $product->requires_prescription))> يصرف بوصفة طبية</label>
            <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> ضمن «الأكثر طلباً» في الرئيسية</label>
        </div>
    </div>
    <div class="inline" style="margin-top:20px">
        <button class="btn">حفظ المنتج</button>
        <a class="btn btn--ghost" href="{{ route('admin.products.index') }}">إلغاء</a>
    </div>
</form>

@if ($product->exists)
<form method="post" action="{{ route('admin.products.destroy', $product) }}" style="margin-top:18px" onsubmit="return confirm('حذف هذا المنتج نهائياً؟')">
    @csrf @method('delete')
    <button class="btn btn--danger">حذف المنتج</button>
</form>
@endif
@endsection
