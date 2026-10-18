@props([
    'status',
])

{{-- The order's status, in the storefront's status colours: what the enum
     reports as its colour names the shared `.badge` modifier. --}}
<span class="badge badge--strong badge--{{ $status->getColor() }}"> {{ $status->getLabel() }} </span>
