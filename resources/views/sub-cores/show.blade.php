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
                        @if($moduleSummaries->has($module->instanceKey))
                            <small>{{ $moduleSummaries->get($module->instanceKey)->detail }}</small>
                        @else
                            <small>Open {{ $module->label }}</small>
                        @endif
                    </span>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            @empty
                <div class="attention-empty">
                    <x-mascot-tip
                        variant="empty"
                        title="No modules are currently available."
                        body="Enabled services will appear here when available."
                    />
                </div>
            @endforelse
        </div>
    </section>

    @if($recentActivity->isNotEmpty())
        <section class="module-hub" aria-labelledby="recent-activity-title">
            <h2 id="recent-activity-title">Recent activity</h2>
            <div class="attention-list">
                @foreach($recentActivity as $item)
                    <a href="{{ route($item->routeName, $item->recordId) }}" class="attention-item">
                        <span class="attention-item__body">
                            <span class="attention-item__kicker">{{ $item->label }}</span>
                            <strong>{{ $item->title }}</strong>
                            <span class="attention-item__detail">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                        </span>
                        <time datetime="{{ $item->updatedAt->toAtomString() }}">{{ $item->updatedAt->diffForHumans() }}</time>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
