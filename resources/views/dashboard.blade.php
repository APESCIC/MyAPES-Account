@extends('layouts.app')

@section('title', 'Dashboard | MyAPES Account')

@section('content')
    @php
        $roleLabel = match (auth()->user()->role) {
            \App\Models\User::ROLE_SERVICE_USER => 'Public',
            \App\Models\User::ROLE_STAFF => 'Staff',
            \App\Models\User::ROLE_ADMIN => 'Admin',
            \App\Models\User::ROLE_SUPERADMIN => 'Superadmin',
            default => 'Account',
        };
    @endphp

    <div class="dashboard-layout">
        <aside class="identity-card" aria-labelledby="welcome-title">
            <div class="identity-card__intro">
                <p class="identity-card__eyebrow">Welcome back,</p>
                <h1 id="welcome-title">{{ auth()->user()->name }}</h1>
                <p class="identity-card__role">
                    <span>Role: <code>{{ auth()->user()->role }}</code></span>
                    <span aria-hidden="true">•</span>
                    <strong>{{ $roleLabel }}</strong>
                </p>
            </div>
            <img
                src="{{ asset('mascot/bearded-dragon-natural.png') }}"
                alt="A realistic bearded dragon resting in a natural habitat"
                class="identity-card__mascot"
                width="1400"
                height="1120"
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
                    <a href="{{ $item['url'] }}" class="attention-item attention-item--{{ $item['type'] }}">
                        <span class="attention-item__icon" aria-hidden="true">
                            <i data-lucide="{{ $item['icon'] }}"></i>
                        </span>
                        <span class="attention-item__body">
                            <span class="attention-item__kicker">{{ $item['service'] }} · {{ $item['label'] }}</span>
                            <strong>{{ $item['title'] }}</strong>
                            <span class="attention-item__detail">
                                <span>{{ str($item['status'])->replace('_', ' ')->title() }}</span>
                                @if($item['priority'])
                                    <span aria-hidden="true">•</span>
                                    <span>Priority: <b class="priority priority--{{ $item['priority'] }}">{{ str($item['priority'])->title() }}</b></span>
                                @elseif($item['context'])
                                    <span aria-hidden="true">•</span>
                                    <span>{{ $item['context'] }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="attention-item__meta">
                            <time datetime="{{ $item['updatedAt']->toAtomString() }}" title="{{ $item['updatedAt']->format('j F Y, H:i') }}">
                                {{ $item['updatedAt']->diffForHumans() }}
                            </time>
                            @if($item['owner'])
                                <span>{{ $item['owner'] }}</span>
                            @endif
                        </span>
                        <i data-lucide="chevron-right" class="attention-item__chevron" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="attention-empty">
                        <i data-lucide="circle-check-big" aria-hidden="true"></i>
                        <div>
                            <strong>You are all caught up.</strong>
                            <p>No open tickets, shelter cases or consultations need attention.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="service-summary" id="service-summary" aria-label="Service totals">
        <a href="{{ route('apes-cic.tickets.index') }}" class="service-summary__item service-summary__item--ticket">
            <i data-lucide="ticket" aria-hidden="true"></i>
            <span class="service-summary__number">{{ $ticketCount }}</span>
            <span class="service-summary__copy">
                <strong>{{ Str::plural('Ticket', $ticketCount) }}</strong>
                <small>{{ $openTicketCount }} open · {{ $highPriorityTicketCount }} high priority</small>
            </span>
            <span class="service-summary__link">View tickets <i data-lucide="arrow-right" aria-hidden="true"></i></span>
        </a>
        <a href="{{ route('shelter.cases.index') }}" class="service-summary__item service-summary__item--shelter">
            <i data-lucide="house" aria-hidden="true"></i>
            <span class="service-summary__number">{{ $shelterCaseCount }}</span>
            <span class="service-summary__copy">
                <strong>Shelter {{ Str::plural('case', $shelterCaseCount) }}</strong>
                <small>{{ $openShelterCaseCount }} open</small>
            </span>
            <span class="service-summary__link">View cases <i data-lucide="arrow-right" aria-hidden="true"></i></span>
        </a>
        <a href="{{ route('petcare.consultations.index') }}" class="service-summary__item service-summary__item--consultation">
            <i data-lucide="messages-square" aria-hidden="true"></i>
            <span class="service-summary__number">{{ $consultationCount }}</span>
            <span class="service-summary__copy">
                <strong>{{ Str::plural('Consultation', $consultationCount) }}</strong>
                <small>{{ $openConsultationCount }} open</small>
            </span>
            <span class="service-summary__link">View consultations <i data-lucide="arrow-right" aria-hidden="true"></i></span>
        </a>
        <a href="{{ route('petcare.pets.index') }}" class="service-summary__item service-summary__item--pet">
            <i data-lucide="heart" aria-hidden="true"></i>
            <span class="service-summary__number">{{ $petProfileCount }}</span>
            <span class="service-summary__copy">
                <strong>Pet {{ Str::plural('profile', $petProfileCount) }}</strong>
                <small>Across Shelter and Pet Care</small>
            </span>
            <span class="service-summary__link">View pets <i data-lucide="arrow-right" aria-hidden="true"></i></span>
        </a>
    </section>
@endsection
