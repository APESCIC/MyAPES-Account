@extends('layouts.app')

@section('title', 'Modules | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <header class="page-heading">
        <div>
            <p class="eyebrow">First-party capability registry</p>
            <h1>Modules</h1>
            <p>Review shipped code, dependencies and guarded installation state across every permanent sub-core.</p>
        </div>
    </header>

    <div class="module-registry" role="region" aria-label="Module compatibility and lifecycle registry">
        @foreach($subCores as $subCore)
            @php
                $shippedRows = [];
                $unavailableChips = [];

                foreach ($modules as $module) {
                    $cell = $cells[$subCore->key.':'.$module->key];
                    $definition = $cell['definition'];

                    if ($definition->isShipped()) {
                        $shippedRows[] = [
                            'module' => $module,
                            'cell' => $cell,
                            'definition' => $definition,
                        ];
                    } else {
                        $unavailableChips[] = [
                            'module' => $module,
                            'definition' => $definition,
                        ];
                    }
                }
            @endphp

            <section class="module-registry__subcore" aria-labelledby="module-subcore-{{ $subCore->key }}">
                <header class="module-registry__subcore-header">
                    <h2 id="module-subcore-{{ $subCore->key }}">{{ $subCore->name }}</h2>
                    <p class="module-registry__subcore-key"><code>{{ $subCore->key }}</code></p>
                </header>

                @if($shippedRows !== [])
                    <ul class="module-registry__rows">
                        @foreach($shippedRows as $row)
                            @php
                                $module = $row['module'];
                                $cell = $row['cell'];
                                $definition = $row['definition'];
                                $installation = $cell['installation'];
                                $status = $definition->codeStatus;
                                $action = $installation === null
                                    ? 'install'
                                    : ($installation->enabled ? 'disable' : 'enable');
                                $stateClass = $installation === null
                                    ? $status->value
                                    : ($installation->enabled ? 'enabled' : 'disabled');
                                $stateLabel = $installation
                                    ? ($installation->enabled ? 'Enabled' : 'Disabled')
                                    : 'Available';
                                $dependencySummary = collect($cell['dependencies'])
                                    ->map(fn (array $dependency): string => $dependency['key'].' ('.($dependency['enabled'] ? 'Enabled' : 'Unavailable').')')
                                    ->implode(', ');
                                if ($dependencySummary === '') {
                                    $dependencySummary = 'None';
                                }
                                $transitionLabel = $cell['transition_at']?->format('Y-m-d H:i') ?? 'Release default';
                                $actorLabel = $cell['actor_id'] ?? 'System';
                            @endphp

                            <li
                                class="module-registry__row module-registry__row--shipped"
                                data-module-cell="{{ $definition->key() }}"
                                data-code-status="{{ $status->value }}"
                            >
                                <div class="module-registry__row-main">
                                    <div class="module-registry__row-title">
                                        <span class="module-registry__module-name">{{ $module->name }}</span>
                                        <small>v{{ $module->version }}</small>
                                    </div>
                                    <strong class="module-state module-state--{{ $stateClass }}">{{ $stateLabel }}</strong>
                                    <p class="module-registry__metrics muted">
                                        {{ $cell['active_record_count'] }} {{ \Illuminate\Support\Str::plural('record', (int) $cell['active_record_count']) }}
                                        · {{ $dependencySummary === 'None' ? 'no deps' : $dependencySummary }}
                                        · last {{ $transitionLabel }}
                                        · actor {{ $actorLabel }}
                                    </p>
                                </div>

                                @can('admin.modules.manage')
                                    <details class="module-registry__manage">
                                        <summary>Manage</summary>
                                        <form
                                            method="post"
                                            action="{{ route('admin.modules.transition', [$subCore->key, $module->key]) }}"
                                            class="module-action-form"
                                            data-module-action-form
                                        >
                                            @csrf
                                            <input type="hidden" name="action" value="{{ $action }}">
                                            @if($installation)
                                                <input type="hidden" name="version" value="{{ $installation->lock_version }}">
                                            @endif
                                            <label>
                                                <input type="checkbox" name="confirm_action" value="1" required>
                                                Confirm {{ $action }}
                                            </label>
                                            <label>
                                                <input type="checkbox" name="confirm_navigation" value="1" required>
                                                Confirm the navigation change
                                            </label>
                                            <button type="submit" class="button button-secondary">
                                                {{ str($action)->title() }}
                                            </button>
                                        </form>
                                    </details>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($unavailableChips !== [])
                    <div class="module-registry__unavailable">
                        <p class="module-registry__unavailable-label muted">Not compatible with this sub-core</p>
                        <ul class="module-registry__chips" aria-label="Unavailable module types for {{ $subCore->name }}">
                            @foreach($unavailableChips as $chip)
                                @php
                                    $module = $chip['module'];
                                    $definition = $chip['definition'];
                                    $status = $definition->codeStatus;
                                @endphp
                                <li
                                    class="module-registry__chip"
                                    data-module-cell="{{ $definition->key() }}"
                                    data-code-status="{{ $status->value }}"
                                >
                                    <strong class="module-state module-state--{{ $status->value }}">{{ $status->label() }}</strong>
                                    <span>{{ $module->name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endsection
