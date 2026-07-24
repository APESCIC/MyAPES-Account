@extends('layouts.app')

@section('title', 'Admin | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('mascot/beardie-wave.svg') }}" alt="Bearded dragon mascot for admin operations" class="brand-mascot">
        <h1>Admin operations</h1>
        <p class="muted">Administrator and superadmin visibility for APES staff operations.</p>
        <div class="grid">
            <div class="panel panel-flat">
                <h3>Total users</h3>
                <div>{{ $totalUsers }}</div>
            </div>
            <div class="panel panel-flat">
                <h3>Staff users</h3>
                <div>{{ $staffUsers }}</div>
            </div>
            <div class="panel panel-flat">
                <h3>Admin users</h3>
                <div>{{ $adminUsers }}</div>
            </div>
        </div>
    </div>
    <div class="panel">
        <h2>Recent accounts</h2>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
            <tbody>
            @foreach($recentUsers as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role }}</td>
                    <td>{{ $user->created_at }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
