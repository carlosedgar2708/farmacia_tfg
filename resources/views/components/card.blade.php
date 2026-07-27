@props([
    'title' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <h3 class="card-title">
            @if ($icon)<i class="{{ $icon }}"></i>@endif{{ $title }}
        </h3>
    @endif
    {{ $slot }}
</div>
