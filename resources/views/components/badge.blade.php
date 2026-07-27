@props([
    'variant' => 'ok', // ok | warn | danger | critical
])

@php
    $variantClass = match ($variant) {
        'warn' => 'warn',
        'danger' => 'danger',
        'critical' => 'critical',
        default => 'ok',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$variantClass}"]) }}>{{ $slot }}</span>
