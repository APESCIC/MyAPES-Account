@extends('layouts.app')

@section('title', $subCore->name.' | MyAPES Account')

@section('content')
    <header class="page-heading module-hub__heading">
        <div>
            <p class="eyebrow">MyAPES Account sub-core</p>
            <h1>{{ $subCore->name }}</h1>
            <p>{{ $subCore->description }}</p>
        </div>
        <span class="module-hub__icon" aria-hidden="true">
            <i data-lucide="{{ $subCore->icon }}"></i>
        </span>
    </header>

    <section class="module-hub" aria-labelledby="available-modules-title">
        <h2 id="available-modules-title">Available modules</h2>
        <div class="module-hub__grid">
            @forelse($modules as $module)
                <a
                    href="{{ route($module->routeName) }}"
                    class="module-hub__card"
                    data-module-instance="{{ $module->instanceKey }}"
                >
                    <i data-lucide="{{ $module->icon }}" aria-hidden="true"></i>
                    <span>
                        <strong>{{ $module->label }}</strong>
                        <small>Open {{ $module->label }}</small>
                    </span>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            @empty
                <div class="attention-empty">
                    <i data-lucide="circle-pause" aria-hidden="true"></i>
                    <div>
                        <strong>No modules are currently available.</strong>
                        <p>Enabled services will appear here when available.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
@endsection
