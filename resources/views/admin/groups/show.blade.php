@extends('layouts.app')

@section('title', 'Group members | MyAPES Core')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="group-members-title">
        <p><a href="{{ route('admin.access.index', ['tab' => 'groups']) }}">← Back to Groups</a></p>
        <h1 id="group-members-title">Members of <code>{{ $group->name }}</code></h1>
        <dl class="admin-definition-list">
            <div>
                <dt>Catalogue status</dt>
                <dd>{{ ucfirst($group->status) }}</dd>
            </div>
            <div>
                <dt>Catalogue member count</dt>
                <dd>{{ $group->member_count ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt>Live directory members</dt>
                <dd>
                    @if($directoryUnavailable)
                        Unavailable
                    @else
                        {{ count($members) }}
                    @endif
                </dd>
            </div>
        </dl>
        <p class="muted">Membership is read live from the directory. This page does not start a directory sync.</p>
    </section>

    <section class="panel" aria-labelledby="group-member-list-title">
        <h2 id="group-member-list-title">Directory members</h2>
        @if($directoryUnavailable)
            <p role="alert">Directory membership could not be loaded. Member details are hidden until the directory is available again.</p>
        @else
            <table>
                <caption>{{ count($members) }} members currently in this group</caption>
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Job title</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->jobTitle ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No members are currently listed for this group in the directory.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </section>
@endsection
