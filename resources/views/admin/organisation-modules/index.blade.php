@extends('layouts.app')

@section('title', 'Admin modules | MyAPES Core')

@section('content')
    @include('admin._navigation')

    <header class="page-heading">
        <div>
            <p class="eyebrow">Organisation areas</p>
            <h1>Admin modules</h1>
            <p>Enable or disable entire organisation areas. Each module lists its shipped plugins and their enablement state. Plugin toggles live on Admin → Plugins.</p>
        </div>
    </header>

    <ul class="module-registry__rows" role="list">
        @foreach($rows as $row)
            @php
                $manifest = $row['manifest'];
                $record = $row['record'];
                $enabled = (bool) $record->enabled;
                $plugins = $row['plugins'];
            @endphp
            <li class="module-registry__row module-registry__row--shipped module-registry__row--{{ $enabled ? 'enabled' : 'disabled' }}">
                <div>
                    <h2>{{ $manifest->name }}</h2>
                    <p class="muted"><code>{{ $manifest->slug }}</code> · {{ $manifest->routePrefix }}</p>
                    <p>{{ $manifest->description }}</p>
                    <p class="muted">State: {{ $enabled ? 'Enabled' : 'Disabled' }}</p>
                    @if($plugins !== [])
                        <ul class="module-registry__chips" aria-label="Plugins for {{ $manifest->name }}">
                            @foreach($plugins as $plugin)
                                <li class="module-registry__chip">
                                    <strong class="module-state module-state--{{ $plugin['enabled'] ? 'enabled' : 'disabled' }}">
                                        {{ $plugin['enabled'] ? 'On' : 'Off' }}
                                    </strong>
                                    <span>{{ $plugin['name'] }}</span>
                                    <code class="muted">{{ $plugin['key'] }}</code>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @can('admin.modules.manage')
                    <form method="post" action="{{ route('admin.organisation-modules.transition', $manifest->slug) }}">
                        @csrf
                        <input type="hidden" name="action" value="{{ $enabled ? 'disable' : 'enable' }}">
                        <button type="submit" class="button {{ $enabled ? 'button-secondary' : '' }}">
                            {{ $enabled ? 'Disable' : 'Enable' }}
                        </button>
                    </form>
                @endcan
            </li>
        @endforeach
    </ul>
@endsection
