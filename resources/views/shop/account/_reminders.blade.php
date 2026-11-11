{{-- تذكير إعادة شراء الأدوية التي يأخذها الزبون باستمرار --}}
<p class="page__sub" style="margin-bottom:16px">لأدوية الضغط والسكري وغيرها: نذكّرك قبل أن ينفد دواؤك، في حسابك وعلى رقمك.</p>

@if ($reminders->count())
    <div class="panel" style="margin-bottom:18px">
        <h2>تذكيراتي</h2>
        @foreach ($reminders as $r)
            <div class="orow" style="margin-top:10px">
                <div>
                    <a class="line__name" href="{{ route('product', $r->product) }}">{{ $r->product->name }}</a>
                    <small>{{ $r->interval_label }} · التذكير القادم {{ $r->next_at->translatedFormat('l j F') }}</small>
                </div>
                <div class="acts">
                    @if ($on['cart'] && $r->product->stock > 0)
                        <form method="post" action="{{ route('cart.add') }}" class="js-add">@csrf<input type="hidden" name="product_id" value="{{ $r->product->id }}"><button class="btn btn--sm btn--buy">أضف للسلة</button></form>
                    @endif
                    <form method="post" action="{{ route('health.reminder.destroy', $r) }}">@csrf @method('delete')<button class="btn btn--sm btn--ghost">إلغاء التذكير</button></form>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="panel">
    <h2>أدوية اشتريتها سابقاً</h2>
    @forelse ($bought as $p)
        <form class="orow" style="margin-top:10px" method="post" action="{{ route('health.reminder', $p) }}">
            @csrf
            <div><a class="line__name" href="{{ route('product', $p) }}">{{ $p->name }}</a><small>{{ $p->category->name ?? '' }}</small></div>
            <div class="acts">
                <label class="sr" for="ev{{ $p->id }}">كل كم يوم</label>
                <select id="ev{{ $p->id }}" name="every_days" class="minisel">@foreach (\App\Models\RefillReminder::INTERVALS as $d => $l)<option value="{{ $d }}" @selected($d === 30)>{{ $l }}</option>@endforeach</select>
                <button class="btn btn--sm">@icon('bell') ذكّرني</button>
            </div>
        </form>
    @empty
        <p class="hint">بعد أول طلب من حسابك تظهر أدويتك هنا لتختار التذكير بها. ويمكنك أيضاً الضغط على «ذكّرني بإعادة الشراء» في صفحة أي منتج.</p>
    @endforelse
</div>
