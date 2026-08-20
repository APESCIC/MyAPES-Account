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
                    @php
                        [$activityType, $activityIcon] = match ($item->moduleKey) {
                            'tickets' => ['ticket', 'ticket'],
                            'cases' => $subCore->key === 'apes-cic'
                                ? ['ticket', 'briefcase-business']
                                : ['shelter', 'house'],
                            'consultations' => ['consultation', 'messages-square'],
                            'pet-profiles' => ['pet', 'heart'],
                            default => ['ticket', 'circle'],
                        };
                    @endphp
                    <x-attention-item-link
                        :href="route($item->routeName, $item->recordId)"
                        :type="$activityType"
                        :icon="$activityIcon"
                        :kicker="$item->label"
                        :title="$item->title"
                        :status="$item->status"
                        :priority="$item->priority"
                        :updated-at="$item->updatedAt"
                    />
                @endforeach
            </div>
        </section>
    @endif
@endsection
