{{-- حقول بيانات الزبون المشتركة بين إتمام الطلب وطلب الوصفة --}}
<div @class(['field', 'bad' => $errors->has('customer_name')])>
    <label for="customer_name">الاسم الكامل</label>
    <input id="customer_name" name="customer_name" value="{{ old('customer_name', $customer->name ?? '') }}" autocomplete="name" required>
    @error('customer_name')<span class="err">{{ $message }}</span>@enderror
</div>
<div @class(['field', 'bad' => $errors->has('phone')])>
    <label for="phone">رقم الموبايل</label>
    <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="07701234567" autocomplete="tel" required>
    @error('phone')<span class="err">{{ $message }}</span>@else<span class="hint">نتصل بك على هذا الرقم لتأكيد الطلب</span>@enderror
</div>
<div @class(['field', 'bad' => $errors->has('area')])>
    <label for="area">{{ $deliveryOn ? 'المنطقة' : 'طريقة الاستلام' }}</label>
    <select id="area" name="area" required @isset($subtotal) data-area data-subtotal="{{ $subtotal - ($discount ?? 0) }}" data-free="{{ $freeOver }}" data-pickup="{{ \App\Support\Util::pickupEnabled() ? \App\Support\Util::pickupLabel() : '' }}" @endisset>
        @if (count($areas) > 1)<option value="">اختر منطقتك</option>@endif
        @foreach ($areas as $name => $fee)
            <option value="{{ $name }}" data-fee="{{ $fee }}" @selected(old('area', $customer->area ?? '') === $name)>{{ $name }} ({{ $fee ? 'توصيل '.number_format($fee).' د.ع' : 'بدون أجور' }})</option>
        @endforeach
    </select>
    @error('area')<span class="err">{{ $message }}</span>@enderror
</div>
<div @class(['field', 'bad' => $errors->has('address')])>
    <label for="address">العنوان <span class="hint" data-address-optional hidden>(اختياري عند الاستلام)</span></label>
    <input id="address" name="address" value="{{ old('address', $customer->address ?? '') }}" placeholder="المحلة، الزقاق، رقم الدار، أقرب نقطة دالة" autocomplete="street-address" data-address>
    @error('address')<span class="err">{{ $message }}</span>@enderror
</div>
<div class="field field--full">
    <label for="notes">ملاحظات (اختياري)</label>
    <textarea id="notes" name="notes" placeholder="مثلاً: وقت التوصيل المناسب، أو سؤال للصيدلاني">{{ old('notes') }}</textarea>
    @error('notes')<span class="err">{{ $message }}</span>@enderror
</div>
