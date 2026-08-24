@props([
    'groups' => [],
    'empty' => 'None recorded',
])

@php
    $groups = collect($groups)
        ->filter(fn ($group) => is_string($group) && $group !== '')
        ->values();
@endphp

@if($groups->isEmpty())
    <span {{ $attributes->class('directory-group-list__empty') }}>{{ $empty }}</span>
@else
    <ul {{ $attributes->class('directory-group-list') }} aria-label="Directory groups">
        @foreach($groups as $group)
            <li>
                <span class="directory-group-list__chip">{{ $group }}</span>
            </li>
        @endforeach
    </ul>
@endif
