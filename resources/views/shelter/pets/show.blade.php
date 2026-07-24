@extends('layouts.app')

@section('title', 'Shelter Pet: '.$pet->name)

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>{{ $pet->name }}</h1>
        <p class="muted">{{ $pet->species }} | Age: {{ $pet->age_years ?? 'n/a' }} | {{ $pet->sex }} | {{ $pet->neutering_status }}</p>
        @if($pet->photo_path)
            <img src="{{ asset('storage/'.$pet->photo_path) }}" alt="{{ $pet->name }}" class="record-photo">
        @endif
        <form method="post" action="{{ route('shelter.pets.update', $pet) }}" enctype="multipart/form-data" class="stack-spaced">
            @csrf
            @method('put')
            <div class="row">
                <div><label>Name</label><input name="name" value="{{ $pet->name }}"></div>
                <div><label>Species</label><input name="species" value="{{ $pet->species }}"></div>
                <div><label>Age (years)</label><input type="number" min="0" max="80" name="age_years" value="{{ $pet->age_years }}"></div>
            </div>
            <div class="row">
                <div><label>Sex</label><select name="sex">@foreach(['male','female','unknown'] as $v)<option value="{{ $v }}" @selected($pet->sex===$v)>{{ $v }}</option>@endforeach</select></div>
                <div><label>Neutering status</label><select name="neutering_status">@foreach(['neutered','not_neutered','unknown'] as $v)<option value="{{ $v }}" @selected($pet->neutering_status===$v)>{{ $v }}</option>@endforeach</select></div>
                <div><label>Photo</label><input type="file" name="photo" accept="image/*"></div>
            </div>
            <label>Health issues</label>
            <textarea name="health_issues">{{ $pet->health_issues }}</textarea>
            <div class="actions">
                <button type="submit">Update pet profile</button>
                <a href="{{ route('shelter.pets.index') }}">Back</a>
            </div>
        </form>
    </div>
@endsection
