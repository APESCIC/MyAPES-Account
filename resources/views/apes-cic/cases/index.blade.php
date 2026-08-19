@extends('layouts.app')

@section('title', 'APES CIC Cases')

@section('content')
    <div class="panel">
        <span class="service-label apes-cic">APES CIC</span>
        <h1>Cases</h1>
        <p class="muted">Track membership, operations, complaints and welfare casework.</p>
        <x-mascot-tip />
    </div>
    @if($canCreateCase)
        <div class="panel">
            <h2>Open a case</h2>
            <form method="post" action="{{ route('apes-cic.cases.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        @foreach($categories as $category)
                            <option value="{{ $category }}">{{ str_replace('_', ' ', $category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        @foreach($priorities as $priority)
                            <option value="{{ $priority }}">{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label for="title">Title</label>
            <input id="title" name="title" value="{{ old('title') }}">
            <label for="details">Details</label>
            <textarea id="details" name="details">{{ old('details') }}</textarea>
                <button type="submit">Open case</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Your available cases</h2>
        @if($cases->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No cases are available to you yet."
                body="When a case is shared with you, or you open one, it will appear here."
            />
        @else
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Priority</th><th>Owner</th><th></th></tr></thead>
                <tbody>
                @foreach($cases as $case)
                    <tr>
                        <td>#{{ $case->id }}</td>
                        <td>{{ $case->title }}</td>
                        <td>{{ str_replace('_', ' ', $case->category) }}</td>
                        <td><span class="status">{{ $case->status }}</span></td>
                        <td>{{ $case->priority }}</td>
                        <td>{{ $case->user->name }}</td>
                        <td><a href="{{ route('apes-cic.cases.show', $case) }}">Open</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $cases->links() }}
        @endif
    </div>
@endsection
