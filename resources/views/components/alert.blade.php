@props([
    'variant' => 'info', // info | success | danger | warning
    'title' => null,
])

@php
    $variantClass = match ($variant) {
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        default => '', // info = .alert base, sin modificador (ya existía así)
    };
@endphp

<div {{ $attributes->merge(['class' => trim("alert {$variantClass}")]) }}>
    @if ($title)<strong>{{ $title }}</strong>@endif
    {{ $slot }}
</div>
