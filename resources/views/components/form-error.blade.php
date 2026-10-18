@props([
    'name',
    'id' => null,
    'errorBag' => 'default',
])

@php
    $errorId = $id ?? 'error-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $messages = $errors->getBag($errorBag)->get($name);
@endphp

@if ($messages)
    <ul id="{{ $errorId }}" class="form-error" role="alert">
        @foreach ($messages as $message)
            <li class="form-error__item">{{ $message }}</li>
        @endforeach
    </ul>
@endif
