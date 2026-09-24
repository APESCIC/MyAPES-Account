@extends('layouts.app')

@section('title', 'Recruitment')

@section('content')
    <div class="panel">
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>Recruitment</h1>
        <p class="muted">Staff, volunteering, and student roles for APES CIC.</p>
        <x-mascot-tip />
    </div>
    <div class="panel" id="list">
        <h2>Roles</h2>
        @if($roles->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No recruitment roles yet."
                body="When a role is created, it will appear here."
            />
        @else
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->title }}</td>
                            <td>{{ $role->category }}</td>
                            <td>{{ $role->status }}</td>
                            <td>
                                <a href="{{ route('apes-cic.recruitment.show', $role) }}">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $roles->links() }}
        @endif
    </div>
    @if($canCreate)
        <div class="panel" id="create">
            <h2>Create role</h2>
            <form method="post" action="{{ route('apes-cic.recruitment.store') }}">
                @csrf
                <div class="row">
                    <div>
                        <label for="role_title">Title</label>
                        <input id="role_title" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div>
                        <label for="role_category">Category</label>
                        <select id="role_category" name="category" required>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label for="role_location">Location</label>
                        <input id="role_location" name="location" value="{{ old('location') }}">
                    </div>
                    <div>
                        <label for="role_commitment">Commitment</label>
                        <input id="role_commitment" name="commitment" value="{{ old('commitment') }}">
                    </div>
                </div>
                <label for="role_summary">Summary</label>
                <input id="role_summary" name="summary" value="{{ old('summary') }}">
                <label for="role_description">Description</label>
                <textarea id="role_description" name="description" required>{{ old('description') }}</textarea>
                <button type="submit">Save recruitment role</button>
            </form>
        </div>
    @endif
@endsection
