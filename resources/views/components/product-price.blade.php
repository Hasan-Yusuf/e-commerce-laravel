@props([
    'currentPrice',
    'originalPrice' => null,
    'discount' => null,
])

@php
    $isDiscounted =
        $discount !== null &&
        $originalPrice !== null &&
        $originalPrice->greaterThan($currentPrice);

    $badge = null;

    if ($isDiscounted) {
        $badge =
            $discount->type === \App\Enums\DiscountType::PERCENTAGE
                ? '-'.Number::format((float) $discount->value, maxPrecision: 3).'%'
                : '-'.\App\Support\Money::amount($discount->value);
    }
@endphp

<p class="product-price">
    <span class="product-price__current">
        {{ $currentPrice }}
    </span>

    @if ($isDiscounted)
        <s class="product-price__original">
            {{ $originalPrice }}
        </s>

        <span class="badge badge--danger product-price__badge">{{ $badge }}</span>
    @else
        <span class="product-price__note"> {{ __('homepage.vat_included') }} </span>
    @endif
</p>
