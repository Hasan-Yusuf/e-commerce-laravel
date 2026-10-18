@props([
    'product',
])

@php
    $specs = $product->type?->specDefinitions ?? collect();
    $specValues = $product->specs ?? [];
@endphp

@if ($specs->isNotEmpty())
    <table class="specs-table">
        <caption class="visually-hidden">
            {{ __('catalog.product.specs_title') }}
        </caption>

        <tbody>
            @foreach ($specs as $spec)
                <tr>
                    <th scope="row" class="specs-table__label">{{ $spec->name }}</th>
                    <td class="specs-table__value">{{ $spec->formatValue($specValues[$spec->name] ?? null) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
