@extends('layouts.app')

@section('title', 'Admin | MyAPES Core')

@section('content')
    @include('admin._navigation')

    @php
        $accounts = $dashboard['accounts'];
        $workload = $dashboard['workload'];
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
    @endphp

    <div class="panel">
        <h1>Admin overview</h1>
        <p class="muted">Day-to-day account health for administrators. Technical charts, directory controls, and plugin lifecycle live in Super Admin.</p>

        <form method="get" action="{{ route('admin.index') }}" class="analytics-range" aria-label="Reporting range">
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

    @can('admin.users.view')
        <div class="panel">
            <h2>Recent accounts</h2>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
                <tbody>
                @foreach($recentUsers as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $authorizationProfile->displayKey($user) }}</td>
                        <td>{{ $user->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="actions">
                <a href="{{ route('admin.users.index', ['account_type' => 'public']) }}">Manage public users</a>
                ·
                <a href="{{ route('admin.users.index', ['account_type' => 'staff']) }}">Manage staff</a>
            </p>
        </div>
    @endcan
@endsection
