@extends('layouts.app')

@section('title', 'APES Pet Care Clinic Pet: '.$pet->name)

@section('content')
    <div class="panel">
        <span class="service-label apes-petcare">APES Pet Care Clinic</span>
        <h1>{{ $pet->name }}</h1>
        <p class="muted">{{ $pet->species }} | Age: {{ $pet->age_years ?? 'n/a' }} | {{ $pet->sex }} | {{ $pet->neutering_status }}</p>
        @if($pet->photo_path)
            <img src="{{ route('petcare.pets.photo', $pet) }}" alt="{{ $pet->name }}" class="record-photo">
        @endif
        @if($canUpdatePet)
        <form method="post" action="{{ route('petcare.pets.update', $pet) }}" enctype="multipart/form-data" class="stack-spaced">
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
                <a href="{{ route('petcare.pets.index') }}">Back</a>
            </div>
        </form>
        @else
            <a href="{{ route('petcare.pets.index') }}">Back</a>
        @endif
    </div>
@endsection
