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

    <div class="module-matrix-wrap" role="region" aria-label="Module compatibility and lifecycle matrix" tabindex="0">
        <table class="module-matrix">
            <thead>
                <tr>
                    <th scope="col">Sub-core</th>
                    @foreach($modules as $module)
                        <th scope="col">
                            {{ $module->name }}
                            <small>v{{ $module->version }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($subCores as $subCore)
                    <tr>
                        <th scope="row">
                            {{ $subCore->name }}
                            <small><code>{{ $subCore->key }}</code></small>
                        </th>
                        @foreach($modules as $module)
                            @php
                                $cell = $cells[$subCore->key.':'.$module->key];
                                $definition = $cell['definition'];
                                $installation = $cell['installation'];
                                $status = $definition->codeStatus;
                                $action = $installation === null
                                    ? 'install'
                                    : ($installation->enabled ? 'disable' : 'enable');
                                $stateClass = $installation === null
                                    ? $status->value
                                    : ($installation->enabled ? 'enabled' : 'disabled');
                            @endphp
                            <td
                                data-module-cell="{{ $definition->key() }}"
                                data-code-status="{{ $status->value }}"
                                @class([
                                    'module-matrix__cell',
                                    'module-matrix__cell--shipped' => $definition->isShipped(),
                                    'module-matrix__cell--unavailable' => ! $definition->isShipped(),
                                ])
                            >
                                <strong class="module-state module-state--{{ $stateClass }}">
                                    @if($definition->isShipped() && $installation)
                                        {{ $installation->enabled ? 'Enabled' : 'Disabled' }}
                                    @elseif($definition->isShipped())
                                        Available
                                    @else
                                        {{ $status->label() }}
                                    @endif
                                </strong>

                                @if($definition->isShipped())
                                    <dl class="module-matrix__facts">
                                        <div>
                                            <dt>Active records</dt>
                                            <dd>{{ $cell['active_record_count'] }}</dd>
                                        </div>
                                        <div>
                                            <dt>Dependencies</dt>
                                            <dd>
                                                @forelse($cell['dependencies'] as $dependency)
                                                    {{ $dependency['key'] }} ({{ $dependency['enabled'] ? 'Enabled' : 'Unavailable' }})@if(! $loop->last), @endif
                                                @empty
                                                    None
                                                @endforelse
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Last transition</dt>
                                            <dd>
                                                {{ $cell['transition_at']?->format('Y-m-d H:i') ?? 'Release default' }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Actor account ID</dt>
                                            <dd>{{ $cell['actor_id'] ?? 'System' }}</dd>
                                        </div>
                                    </dl>

                                    @can('admin.modules.manage')
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
                                    @endcan
                                @else
                                    <p class="muted">
                                        {{ $status === \App\Modules\ModuleCodeStatus::CodeNotShipped
                                            ? 'Compatible code is not included in this release.'
                                            : 'This module type is not compatible with this sub-core.' }}
                                    </p>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
