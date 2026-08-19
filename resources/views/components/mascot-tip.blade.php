@props([
    'variant' => 'aside',
    'title' => null,
    'body' => null,
])

@php
    $tip = is_string($title) && is_string($body)
        ? ['title' => $title, 'body' => $body]
        : app(\App\Support\MascotTips::class)->forRoute(request()->route()?->getName());
    $image = $variant === 'hero' ? 'mascot/spike-welcome.png' : 'mascot/spike-tip.png';
@endphp

@if($tip)
    <aside {{ $attributes->class(['mascot-tip', 'mascot-tip--'.$variant]) }}>
        <img
            src="{{ asset($image) }}"
            alt=""
            class="{{ $variant === 'hero' ? 'hero-image' : 'brand-mascot' }}"
            width="512"
            height="512"
        >
        <div>
            <p class="mascot-tip__kicker">Spike says</p>
            <p><strong>{{ $tip['title'] }}</strong></p>
            <p>{{ $tip['body'] }}</p>
        </div>
    </aside>
@endif
