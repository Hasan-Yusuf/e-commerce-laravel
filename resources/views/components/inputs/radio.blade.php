@props([
    'name',
    'label' => null,
    // The line of explanation under the choice. Optional: a choice with only a
    // name keeps the plain radio look.
    'description' => null,
    'value' => '1',
    'id' => null,
    'checked' => false,
    // The class naming the control: it is the label's block, and the parts
    // inside it (__body, __title, __description) are named after it, so a field
    // that renames the wrapper (a filter, a "remember me") keeps its own look.
    'wrapperClass' => 'radio-field',
])

@php
    $inputId = $id ?? 'field-'.str_replace(
        ['[', ']', '.'],
        ['-', '', '-'],
        $name.'-'.$value,
    );

    $descriptionId = $description === null ? null : $inputId.'-description';

    // The explanation is part of what the choice says, so a screen reader reads
    // it out with the name rather than only showing it.
    $describedBy = array_filter([
        $attributes->get('aria-describedby'),
        $descriptionId,
    ]);

    $defaults = ['id' => $inputId];

    if ($describedBy !== []) {
        $defaults['aria-describedby'] = implode(' ', $describedBy);
    }
@endphp

<label
    @class([
        $wrapperClass,
        $wrapperClass.'--with-description' => $description !== null,
    ])
    for="{{ $inputId }}"
>
    <input
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        @checked($checked)
        {{ $attributes->except('aria-describedby')->merge($defaults) }}
    />

    <span class="{{ $wrapperClass }}__body">
        <span class="{{ $wrapperClass }}__title">{{ $label ?? $slot }}</span>

        @if ($description !== null)
            <span class="{{ $wrapperClass }}__description" id="{{ $descriptionId }}"> {{ $description }} </span>
        @endif
    </span>
</label>
