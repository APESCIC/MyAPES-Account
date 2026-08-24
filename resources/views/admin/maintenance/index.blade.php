@extends('layouts.app')

@section('title', 'Super Admin maintenance | MyAPES Core')

@section('content')
    @include('superadmin._navigation')

    <header class="page-heading">
        <div>
            <p class="eyebrow">Guarded recovery controls</p>
            <h1>Super Admin maintenance</h1>
            <p>Manage Laravel maintenance mode without creating a secret bypass route.</p>
        </div>
    </header>

    <section class="panel" aria-labelledby="maintenance-state-heading">
        <h2 id="maintenance-state-heading">{{ $active ? 'Application is in maintenance' : 'Application is available' }}</h2>
        <p>Laravel's native maintenance store is authoritative.</p>
        @if($current)
            <dl class="detail-list">
                <div><dt>State</dt><dd>{{ str($current->state)->replace('_', ' ')->title() }}</dd></div>
                <div><dt>Message</dt><dd class="maintenance-message">{{ $current->message }}</dd></div>
                <div><dt>Started</dt><dd>{{ $current->activated_at?->format('Y-m-d H:i T') ?? 'Pending reconciliation' }}</dd></div>
                <div><dt>Planned end</dt><dd>{{ $current->planned_end_at?->format('Y-m-d H:i T') ?? 'Not specified' }}</dd></div>
                <div><dt>Initiated by</dt><dd>{{ $current->initiator?->name ?? 'System / CLI' }}</dd></div>
                @if($current->deactivationRequester)
                    <div><dt>End requested by</dt><dd>{{ $current->deactivationRequester->name }}</dd></div>
                @endif
            </dl>
        @endif
        @if($problem)
            <p class="error-list">{{ $problem }}</p>
        @endif
    </section>

    <section class="panel" aria-labelledby="queue-impact-heading">
        <h2 id="queue-impact-heading">Queue processing pauses</h2>
        <p>The Redis queue worker runs without <code>--force</code>. Jobs remain durable and resume after maintenance ends.</p>
    </section>

    @unless($problem)
        @unless($active)
            <section class="panel" aria-labelledby="activate-maintenance-heading">
                <h2 id="activate-maintenance-heading">Activate maintenance</h2>
                <p>Public users and ordinary staff will receive the maintenance response. Health, staff authentication and this recovery console remain available.</p>
                <form method="post" action="{{ route('admin.maintenance.activate') }}" class="stacked-form">
                    @csrf
                    <label for="maintenance-message">Public message</label>
                    <textarea id="maintenance-message" name="message" maxlength="500" required>{{ old('message') }}</textarea>
                    <label for="maintenance-planned-end">Planned end (optional)</label>
                    <input id="maintenance-planned-end" type="datetime-local" name="planned_end_at" value="{{ old('planned_end_at') }}">
                    <label>
                        <input type="checkbox" name="confirm_activation" value="1" required>
                        I confirm that public users and ordinary staff will be blocked.
                    </label>
                    <button type="submit" class="button danger-btn">Activate maintenance</button>
                </form>
            </section>
        @else
            <section class="panel" aria-labelledby="deactivate-maintenance-heading">
                <h2 id="deactivate-maintenance-heading">End maintenance</h2>
                <p>Public and staff traffic will resume immediately. Queued jobs will begin processing again.</p>
                <form method="post" action="{{ route('admin.maintenance.deactivate') }}" class="stacked-form">
                    @csrf
                    <label>
                        <input type="checkbox" name="confirm_deactivation" value="1" required>
                        I confirm that application traffic and queue processing may resume.
                    </label>
                    <button type="submit" class="button">Deactivate maintenance</button>
                </form>
            </section>
        @endunless
    @endunless

    <section class="panel" aria-labelledby="maintenance-history-heading">
        <h2 id="maintenance-history-heading">Recent maintenance windows</h2>
        @if($history->isEmpty())
            <p>No maintenance windows have been recorded.</p>
        @else
            <div class="table-wrap" role="region" aria-label="Recent maintenance history" tabindex="0">
                <table>
                    <caption>The 25 most recent maintenance windows</caption>
                    <thead>
                        <tr>
                            <th scope="col">State</th>
                            <th scope="col">Message</th>
                            <th scope="col">Started</th>
                            <th scope="col">Initiated by</th>
                            <th scope="col">Ended</th>
                            <th scope="col">Ended by</th>
                            <th scope="col">Failure</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $window)
                            <tr>
                                <td>{{ str($window->state)->replace('_', ' ')->title() }}</td>
                                <td class="maintenance-message">{{ $window->message }}</td>
                                <td>{{ $window->activated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $window->initiator?->name ?? 'System / CLI' }}</td>
                                <td>{{ $window->deactivated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td>{{ $window->endingActor?->name ?? '—' }}</td>
                                <td>{{ $window->failure_summary ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
