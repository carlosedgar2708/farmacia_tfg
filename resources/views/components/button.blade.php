@props([
    'variant' => 'secondary', // primary | secondary | danger | ghost
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'disabled' => false,
])

@php
    $variantClass = match ($variant) {
        'primary' => 'btn',
        'danger' => 'btn danger',
        'ghost' => 'btn-ghost',
        default => 'btn-outline', // secondary
    };

    // Un enlace deshabilitado no es un patrón HTML válido (el atributo
    // `disabled` no existe en <a>) — si está deshabilitado, siempre se
    // renderiza como <button disabled>, sin importar si vino `href`.
    $renderAsLink = $href && !$disabled;
@endphp

@if ($renderAsLink)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $variantClass]) }}>
        @if ($icon)<i class="{{ $icon }}"></i>@endif{{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        @if ($disabled) aria-disabled="true" @endif
        {{ $attributes->merge(['class' => $variantClass]) }}
    >
        @if ($icon)<i class="{{ $icon }}"></i>@endif{{ $slot }}
    </button>
@endif
