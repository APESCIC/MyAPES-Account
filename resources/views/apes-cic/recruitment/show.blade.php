@extends('layouts.app')

@section('title', 'Recruitment: '.$role->title)

@section('content')
    @inject('ukDateTime', \App\Support\UkDateTime::class)
    <div class="panel">
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>{{ $role->title }}</h1>
        <p class="muted">{{ $role->category }} | {{ $role->status }}</p>
        <dl class="ticket-meta">
            @if($revealCreator)
                <div>
                    <dt>Created by</dt>
                    <dd>
                        {{ $role->creator?->name ?? '—' }}
                        @if($role->creator)
                            <br><small class="muted">{{ $role->creator->email }}</small>
                        @endif
                    </dd>
                </div>
            @endif
            <div>
                <dt>Created</dt>
                <dd>{{ $ukDateTime->formatDate($role->created_at) ?? '—' }}</dd>
            </div>
            <div>
                <dt>ID</dt>
                <dd>#{{ $role->id }}</dd>
            </div>
            @if($role->published_at)
                <div>
                    <dt>Published</dt>
                    <dd>{{ $ukDateTime->formatDate($role->published_at) }}</dd>
                </div>
            @endif
            @if($role->closed_at)
                <div>
                    <dt>Closed</dt>
                    <dd>{{ $ukDateTime->formatDate($role->closed_at) }}</dd>
                </div>
            @endif
        </dl>
        @if($role->summary)
            <p>{{ $role->summary }}</p>
        @endif
        @if($role->location || $role->commitment)
            <p class="muted">
                @if($role->location)Location: {{ $role->location }}@endif
                @if($role->location && $role->commitment) | @endif
                @if($role->commitment)Commitment: {{ $role->commitment }}@endif
            </p>
        @endif
        <div class="stack-spaced">
            {!! nl2br(e($role->description)) !!}
        </div>
        @if($canPublish || $canClose)
            <div class="actions">
                @if($canPublish)
                    <form method="post" action="{{ route('apes-cic.recruitment.publish', $role) }}">
                        @csrf
                        <button type="submit">Publish / open</button>
                    </form>
                @endif
                @if($canClose)
                    <form method="post" action="{{ route('apes-cic.recruitment.close', $role) }}">
                        @csrf
                        <button type="submit">Close role</button>
                    </form>
                @endif
            </div>
        @endif
        @if($canUpdate)
            <form method="post" action="{{ route('apes-cic.recruitment.update', $role) }}" class="stack-spaced">
                @csrf
                @method('put')
                <div class="row">
                    <div>
                        <label for="role_title">Title</label>
                        <input id="role_title" name="title" value="{{ old('title', $role->title) }}" required>
                    </div>
                    <div>
                        <label for="role_category">Category</label>
                        <select id="role_category" name="category" required>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected(old('category', $role->category) === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div>
                        <label for="role_location">Location</label>
                        <input id="role_location" name="location" value="{{ old('location', $role->location) }}">
                    </div>
                    <div>
                        <label for="role_commitment">Commitment</label>
                        <input id="role_commitment" name="commitment" value="{{ old('commitment', $role->commitment) }}">
                    </div>
                </div>
                <label for="role_summary">Summary</label>
                <input id="role_summary" name="summary" value="{{ old('summary', $role->summary) }}">
                <label for="role_description">Description</label>
                <textarea id="role_description" name="description" required>{{ old('description', $role->description) }}</textarea>
                <div class="actions">
                    <button type="submit">Update recruitment role</button>
                    <a href="{{ route('apes-cic.recruitment.index') }}">Back</a>
                </div>
            </form>
        @else
            <a href="{{ route('apes-cic.recruitment.index') }}">Back</a>
        @endif
    </div>
@endsection
