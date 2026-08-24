@extends('layouts.app')

@section('title', $subCore->name.' | MyAPES Core')

@section('content')
    <div class="service-dashboard service-dashboard--{{ $subCore->key }}">
        <header class="page-heading service-dashboard__heading">
            <div>
                <p class="eyebrow">MyAPES Core service</p>
                <h1>{{ $subCore->name }}</h1>
                <p>{{ $subCore->description }}</p>
            </div>
            <span class="service-dashboard__icon" aria-hidden="true">
                <i data-lucide="{{ $subCore->icon }}"></i>
            </span>
        </header>

        <div class="service-dashboard__top dashboard-layout">
            <aside class="service-dashboard__intro" aria-labelledby="service-intro-title">
                <h2 id="service-intro-title">What you can do here</h2>
                <p>{{ $subCore->description }}</p>
                @if($modules !== [])
                    <ul class="service-dashboard__tools">
                        @foreach($modules as $module)
                            <li>{{ $module->label }}</li>
                        @endforeach
                    </ul>
                @endif
            </aside>

            <section class="attention-panel" aria-labelledby="attention-title">
                <header class="attention-panel__header">
                    <span class="attention-panel__compass" aria-hidden="true">
                        <i data-lucide="compass"></i>
                    </span>
                    <div>
                        <h2 id="attention-title">What needs your attention</h2>
                        <p>Open items in {{ $subCore->name }} that may need a response or follow-up.</p>
                    </div>
                </header>

                <div class="attention-list">
                    @forelse($attentionItems as $item)
                        <x-attention-item-link
                            :href="route($item->routeName, $item->recordId)"
                            :type="$item->type"
                            :icon="$item->icon"
                            :kicker="$item->label"
                            :title="$item->title"
                            :status="$item->status"
                            :priority="$item->priority"
                            :context="$item->context"
                            :owner="$item->owner"
                            :updated-at="$item->updatedAt"
                        />
                    @empty
                        <div class="attention-empty">
                            <x-mascot-tip
                                variant="empty"
                                title="You are all caught up."
                                :body="'No open items need your attention for '.$subCore->name.' right now.'"
                            />
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        @if($summaryGroup !== null && $summaryGroup->summaries !== [])
            <section
                class="service-dashboard__section service-summary service-summary--hub service-summary__group service-summary__group--{{ $summaryGroup->key }}"
                aria-labelledby="support-services-title"
            >
                <header class="service-summary__header">
                    <h2 class="service-summary__heading" id="support-services-title">
                        Available plugins
                    </h2>
                </header>
                <div
                    class="service-summary__items"
                    style="--module-count: {{ count($summaryGroup->summaries) }}"
                >
                    @foreach($summaryGroup->summaries as $summary)
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
                        </a>
                    @endforeach
                </div>
            </section>
        @elseif($modules === [])
            <section class="service-dashboard__section service-dashboard__empty" aria-labelledby="support-services-title">
                <h2 id="support-services-title">Available plugins</h2>
                <div class="attention-empty">
                    <x-mascot-tip
                        variant="empty"
                        title="No plugins are currently available."
                        body="Plugins will appear here when they are enabled for you."
                    />
                </div>
            </section>
        @endif

        @if($modules !== [])
            <section class="service-dashboard__section service-dashboard__quick-links" aria-labelledby="quick-links-title">
                <h2 id="quick-links-title">Quick links</h2>
                <div class="service-dashboard__quick-links-grid">
                    @foreach($modules as $module)
                        @php
                            $permissionPrefix = $module->subCoreKey.'.'.$module->moduleKey;
                            $createLabel = match ($module->moduleKey) {
                                'tickets' => 'Create ticket',
                                'cases' => 'Create case',
                                'pet-profiles' => 'Add pet profile',
                                'consultations' => 'Book consultation',
                                default => 'Create '.$module->label,
                            };
                        @endphp
                        <a href="{{ route($module->routeName) }}" class="service-dashboard__quick-link">
                            View {{ $module->label }}
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </a>
                        @if(auth()->user()->can($permissionPrefix.'.create'))
                            <a href="{{ route($module->routeName) }}" class="service-dashboard__quick-link service-dashboard__quick-link--action">
                                {{ $createLabel }}
                                <i data-lucide="plus" aria-hidden="true"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if($recentActivity->isNotEmpty())
            <section class="service-dashboard__section service-dashboard__recent" aria-labelledby="recent-updates-title">
                <h2 id="recent-updates-title">Recent updates</h2>
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
    </div>
@endsection
