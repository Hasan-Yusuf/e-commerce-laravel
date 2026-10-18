@props([
    // The query parameters a form has to carry on: a scalar renders one field,
    // a list renders one field per entry (`brand[]`), and a keyed array keeps
    // its keys (`specs[3][min]`) so a nested filter survives the round trip.
    'params' => [],
])

@foreach ($params as $name => $value)
    @if (is_array($value))
        @foreach ($value as $key => $entry)
            @php
                $field = array_is_list($value) ? $name.'[]' : $name.'['.$key.']';
            @endphp

            @if (is_array($entry))
                <x-inputs.hidden-params :params="[$field => $entry]" />
            @elseif ($entry !== null && $entry !== '')
                <x-inputs.hidden :name="$field" :value="$entry" />
            @endif
        @endforeach
    @elseif ($value !== null && $value !== '')
        <x-inputs.hidden :name="$name" :value="$value" />
    @endif
@endforeach
