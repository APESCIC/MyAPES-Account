@extends('layouts.app')

@section('title', 'Super Admin | MyAPES Core')

@push('head')
    @vite('resources/js/admin-analytics.js')
@endpush

@section('content')
    @include('superadmin._navigation')

    @php
        $accounts = $dashboard['accounts'];
        $workload = $dashboard['workload'];
        $median = $workload['median_closure_minutes'];
        $identityLabels = [
            'local' => 'Local',
            'cloudron_oidc' => 'Cloudron OIDC',
            'hybrid' => 'Hybrid',
        ];
        $accessLabels = [
            'service-user' => 'Public',
            'staff' => 'Staff',
            'administrator' => 'Administrator',
            'super-admin' => 'Super-admin',
        ];
        $alertLabels = [
            'disabled' => 'Disabled',
            'blocked' => 'Blocked',
            'active-records' => 'Active records',
        ];
        $chartData = [
            'days' => $workload['days'],
            'created' => $workload['created_per_day'],
            'closed' => $workload['closed_per_day'],
            'instances' => $workload['by_instance'],
        ];
    @endphp

    <div class="panel">
        <p class="eyebrow">Technical operations</p>
        <h1>Super Admin overview</h1>
        <p class="muted">Directory, plugin, and privileged diagnostics for Cloudron super-admins. Day-to-day account work stays in Admin.</p>

        <form method="get" action="{{ route('superadmin.index') }}" class="analytics-range" aria-label="Reporting range">
            <fieldset>
                <legend>Reporting range</legend>
                @foreach($ranges as $option)
                    <label>
                        <input type="radio" name="range" value="{{ $option }}" @checked($range === $option) onchange="this.form.submit()">
                        Last {{ $option }} days
                    </label>
                @endforeach
                <button type="submit">Update range</button>
            </fieldset>
        </form>

        <div class="grid analytics-kpis" role="list">
            <div class="panel panel-flat" role="listitem">
                <h3>Total accounts</h3>
                <div data-kpi="total-accounts">{{ $accounts['total'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Created in range</h3>
                <div data-kpi="created-in-range">{{ $accounts['created_in_range'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Suspended</h3>
                <div data-kpi="suspended-accounts">{{ $accounts['suspended'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Open workload</h3>
                <div data-kpi="open-workload">{{ $workload['open'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>High or urgent</h3>
                <div data-kpi="high-or-urgent">{{ $workload['high_or_urgent'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Unassigned</h3>
                <div data-kpi="unassigned">{{ $workload['unassigned'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Enabled plugins</h3>
                <div data-kpi="enabled-modules">{{ $dashboard['modules']['enabled'] }} / {{ $dashboard['modules']['installed'] }}</div>
            </div>
            <div class="panel panel-flat" role="listitem">
                <h3>Median closure</h3>
                <div data-kpi="median-closure">
                    @if($median === null)
                        not available
                    @else
                        {{ number_format($median, 1) }} minutes
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Accounts by identity and access class</h2>
        <div class="grid">
            <table data-table="identity-types">
                <caption>Account totals by identity type</caption>
                <thead><tr><th scope="col">Identity type</th><th scope="col">Accounts</th></tr></thead>
                <tbody>
                @foreach($accounts['by_identity_type'] as $type => $count)
                    <tr>
                        <th scope="row">{{ $identityLabels[$type] ?? $type }}</th>
                        <td>{{ $count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <table data-table="access-classes">
                <caption>Account totals by protected access class</caption>
                <thead><tr><th scope="col">Access class</th><th scope="col">Accounts</th></tr></thead>
                <tbody>
                @foreach($accounts['by_access_class'] as $class => $count)
                    <tr>
                        <th scope="row">{{ $accessLabels[$class] ?? $class }}</th>
                        <td>{{ $count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Created versus closed</h2>
        <p class="muted">Daily created and closed items for the selected range. Patterned series, not colour alone.</p>
        <div class="analytics-chart-frame" data-chart-frame="trend">
            <canvas id="analytics-trend-chart" role="img" aria-labelledby="analytics-trend-caption"></canvas>
        </div>
        <table id="analytics-trend-table" data-table="created-versus-closed">
            <caption id="analytics-trend-caption">Created versus closed items per day</caption>
            <thead>
                <tr>
                    <th scope="col">Day</th>
                    <th scope="col">Created</th>
                    <th scope="col">Closed</th>
                </tr>
            </thead>
            <tbody>
            @foreach($workload['days'] as $index => $day)
                <tr>
                    <th scope="row">{{ $day }}</th>
                    <td>{{ $workload['created_per_day'][$index] }}</td>
                    <td>{{ $workload['closed_per_day'][$index] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2>Open workload by service</h2>
        <p class="muted">Currently open tickets, cases, and consultations by installed plugin.</p>
        <div class="analytics-chart-frame" data-chart-frame="workload">
            <canvas id="analytics-workload-chart" role="img" aria-labelledby="analytics-workload-caption"></canvas>
        </div>
        <table id="analytics-workload-table" data-table="workload-by-service">
            <caption id="analytics-workload-caption">Open workload by service and plugin</caption>
            <thead>
                <tr>
                    <th scope="col">Service</th>
                    <th scope="col">Open</th>
                    <th scope="col">High or urgent</th>
                    <th scope="col">Unassigned</th>
                </tr>
            </thead>
            <tbody>
            @forelse($workload['by_instance'] as $instance)
                <tr data-instance="{{ $instance['key'] }}">
                    <th scope="row">{{ $instance['sub_core'] }} — {{ $instance['module'] }}</th>
                    <td>{{ $instance['open'] }}</td>
                    <td>{{ $instance['high_or_urgent'] }}</td>
                    <td>{{ $instance['unassigned'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No plugin analytics are available.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2>Operational context</h2>
        <p data-maintenance-state="{{ $dashboard['maintenance']['active'] ? 'active' : 'inactive' }}">
            Maintenance is
            <strong>{{ $dashboard['maintenance']['active'] ? 'active' : 'inactive' }}</strong>
            @if($dashboard['maintenance']['message'])
                — {{ $dashboard['maintenance']['message'] }}
            @endif
        </p>
        <table data-table="module-alerts">
            <caption>Disabled, blocked, or active-record plugin warnings</caption>
            <thead><tr><th scope="col">Plugin</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @forelse($dashboard['module_alerts'] as $alert)
                <tr data-alert-kind="{{ $alert['kind'] }}">
                    <th scope="row">{{ $alert['label'] }}</th>
                    <td>{{ $alertLabels[$alert['kind']] ?? $alert['kind'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No plugin warnings.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <table data-table="privileged-events">
            <caption>Recent privileged audit events</caption>
            <thead>
                <tr>
                    <th scope="col">Action</th>
                    <th scope="col">Actor</th>
                    <th scope="col">Time</th>
                </tr>
            </thead>
            <tbody>
            @forelse($dashboard['privileged_events'] as $event)
                <tr>
                    <th scope="row">{{ $event['event'] }}</th>
                    <td>{{ $event['actor'] }}</td>
                    <td>{{ $event['occurred_at'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No privileged events in the audit log.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <script type="application/json" id="admin-analytics-chart-data">@json($chartData)</script>
@endsection
