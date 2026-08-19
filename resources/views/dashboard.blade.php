@extends('layouts.app')

@section('title', 'Dashboard | MyAPES Account')

@section('content')
    @php
        $authorizationProfile = app(\App\Services\AuthorizationProfile::class);
        $roleKey = $authorizationProfile->displayKey(auth()->user());
        $roleLabel = $authorizationProfile->displayLabel(auth()->user());
    @endphp

    <div class="dashboard-layout">
        <aside class="identity-card" aria-labelledby="welcome-title">
            <div class="identity-card__intro">
                <p class="identity-card__eyebrow">Welcome back,</p>
                <h1 id="welcome-title">{{ auth()->user()->name }}</h1>
                <p class="identity-card__role">
                    <span>Role: <code>{{ $roleKey }}</code></span>
                    <span aria-hidden="true">•</span>
                    <strong>{{ $roleLabel }}</strong>
                </p>
            </div>
            <img
                src="{{ asset('mascot/spike-welcome.png') }}"
                alt="Spike, the cartoon MyAPES bearded dragon mascot"
                class="identity-card__mascot"
                width="1024"
                height="1024"
            >
            <div class="identity-card__mission">
                <span class="identity-card__mission-icon" aria-hidden="true">
                    <i data-lucide="sprout"></i>
                </span>
                <p><strong>Our mission:</strong> Protect exotic species through rescue, rehabilitation, education and conservation.</p>
            </div>
        </aside>

        <section class="attention-panel" aria-labelledby="attention-title">
            <header class="attention-panel__header">
                <span class="attention-panel__compass" aria-hidden="true">
                    <i data-lucide="compass"></i>
                </span>
                <div>
                    <h2 id="attention-title">What needs your attention next?</h2>
                    <p>Here are the most recently updated open items across MyAPES.</p>
                </div>
            </header>

            <div class="attention-list">
                @forelse($attentionItems as $item)
                    <a href="{{ route($item->routeName, $item->recordId) }}" class="attention-item attention-item--{{ $item->type }}">
                        <span class="attention-item__icon" aria-hidden="true">
                            <i data-lucide="{{ $item->icon }}"></i>
                        </span>
                        <span class="attention-item__body">
                            <span class="attention-item__kicker">{{ $item->service }} · {{ $item->label }}</span>
                            <strong>{{ $item->title }}</strong>
                            <span class="attention-item__detail">
                                <span>{{ str($item->status)->replace('_', ' ')->title() }}</span>
                                @if($item->priority)
                                    <span aria-hidden="true">•</span>
                                    <span>Priority: <b class="priority priority--{{ $item->priority }}">{{ str($item->priority)->title() }}</b></span>
                                @elseif($item->context)
                                    <span aria-hidden="true">•</span>
                                    <span>{{ $item->context }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="attention-item__meta">
                            <time datetime="{{ $item->updatedAt->toAtomString() }}" title="{{ $item->updatedAt->format('j F Y, H:i') }}">
                                {{ $item->updatedAt->diffForHumans() }}
                            </time>
                            @if($item->owner)
                                <span>{{ $item->owner }}</span>
                            @endif
                        </span>
                        <i data-lucide="chevron-right" class="attention-item__chevron" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="attention-empty">
                        <x-mascot-tip
                            variant="empty"
                            title="You are all caught up."
                            body="No open tickets, shelter cases or consultations need attention."
                        />
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="service-summary" id="service-summary" aria-label="Service totals">
        @foreach($moduleSummaries as $summary)
            <a
                href="{{ route($summary->routeName) }}"
                class="service-summary__item service-summary__item--{{ $summary->style }}"
                data-module-instance="{{ $summary->instanceKey }}"
            >
                <i data-lucide="{{ $summary->icon }}" aria-hidden="true"></i>
                <span class="service-summary__number">{{ $summary->total }}</span>
                <span class="service-summary__copy">
                    <strong>{{ $summary->label }}</strong>
                    <small>{{ $summary->detail }}</small>
                </span>
                <span class="service-summary__link">Open module <i data-lucide="arrow-right" aria-hidden="true"></i></span>
            </a>
        @endforeach
    </section>
@endsection
