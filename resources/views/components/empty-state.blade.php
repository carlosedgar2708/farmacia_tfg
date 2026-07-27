@props([
    'title' => null,
    'message' => null,
    'icon' => null,
    'compact' => false,
])

@php
    $baseClass = $compact ? 'empty-state--compact' : 'empty-box';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    @if ($compact)
        @if ($icon)<i class="{{ $icon }}"></i>@endif
        <span>
            @if ($title)<strong>{{ $title }}</strong>@endif
            {{ $message }}
        </span>
    @else
        @if ($title)<strong>{{ $title }}</strong><br>@endif{{ $message }}
    @endif
</div>
@if (! $slot->isEmpty())
    <div class="mt-12">{{ $slot }}</div>
@endif
