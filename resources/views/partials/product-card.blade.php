<article class="pcard">
    <a class="pcard__media" style="--tint: {{ $p->tint }}" href="{{ route('product', $p) }}" tabindex="-1" aria-hidden="true" data-track="product">
        @if ($p->image_url)
            <img src="{{ $p->image_url }}" alt="" loading="lazy">
        @else
            @icon($p->icon)
        @endif
        @if ($p->stock < 1)
            <span class="tag tag--out">غير متوفر</span>
        @elseif ($p->discount)
            <span class="tag tag--off">خصم <span class="num">{{ $p->discount }}%</span></span>
        @endif
        @if ($p->requires_prescription)
            <span class="tag tag--rx">بوصفة طبية</span>
        @endif
    </a>
    <div class="pcard__favwrap">
        <form method="post" action="{{ route('favorite', $p) }}" class="js-fav">
            @csrf
            <button @class(['fav', 'on' => in_array($p->id, $favoriteIds ?? [], true)]) data-track="favorite" aria-label="أعلمني عند انخفاض سعر {{ $p->name }}" title="أعلمني عند انخفاض السعر">@icon('heart')</button>
        </form>
    </div>
    <div class="pcard__body">
        <a class="pcard__name" href="{{ route('product', $p) }}" data-track="product">{{ $p->name }}</a>
        @if ($p->scientific_name)
            <div class="pcard__sci">{{ $p->scientific_name }}</div>
        @endif
        <div class="pcard__foot">
            <div class="price">
                @if ($p->discount)<s>@iqd($p->old_price)</s>@endif
                <b>@iqd($p->price)</b>
            </div>
            <form method="post" action="{{ route('cart.add') }}" class="js-add">
                @csrf
                <input type="hidden" name="product_id" value="{{ $p->id }}">
                <button class="btn btn--sm btn--buy" data-track="add-to-cart" @disabled($p->stock < 1) aria-label="أضف {{ $p->name }} للسلة">أضف للسلة</button>
            </form>
        </div>
    </div>
</article>
