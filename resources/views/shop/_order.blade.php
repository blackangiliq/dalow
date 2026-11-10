{{-- حالة الطلب وتفاصيله --}}
@php
    $flow = ['new', 'confirmed', 'preparing', 'shipping', 'delivered'];
    $pos = array_search($order->status, $flow, true);
@endphp
@if ($order->status === 'cancelled')
    <div class="note note--bad">@icon('alert') <p>هذا الطلب ملغي. للاستفسار اتصل بالصيدلية.</p></div>
@else
    <ol class="track" aria-label="حالة الطلب">
        @foreach ($flow as $i => $s)
            <li @class(['on' => $i <= $pos, 'passed' => $i < $pos])>{{ \App\Models\Order::STATUSES[$s] === 'جديد' ? 'استلمنا الطلب' : \App\Models\Order::STATUSES[$s] }}</li>
        @endforeach
    </ol>
@endif

<div class="panel" style="margin-top:26px;text-align:start">
    @if ($order->type === 'prescription')
        <p>طلب بوصفة طبية. سيتصل بك الصيدلاني على الرقم <span class="num">{{ $order->phone }}</span> بالسعر والتفاصيل.</p>
    @else
        @foreach ($order->items as $item)
            <div class="sum" style="margin-bottom:8px"><div><dt>{{ $item->name }} × <span class="num">{{ $item->qty }}</span></dt><dd>@iqd($item->price * $item->qty)</dd></div></div>
        @endforeach
        <dl class="sum" style="border-top:1.5px solid var(--line);padding-top:12px;margin-top:12px">
            <div><dt>{{ \App\Support\Util::isPickup($order->area) ? $order->area : 'التوصيل إلى '.$order->area }}</dt><dd>{{ $order->delivery_fee ? \App\Support\Util::money($order->delivery_fee) : 'بدون أجور' }}</dd></div>
            <div class="total"><dt>المجموع (الدفع عند الاستلام)</dt><dd>@iqd($order->total)</dd></div>
        </dl>
    @endif
</div>
