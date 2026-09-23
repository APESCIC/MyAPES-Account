@extends('layouts.app')

@section('title', 'APES Pet Care Clinic Pet Profiles')

@section('content')
    <div class="panel">
        <span class="service-label apes-petcare">APES Pet Care Clinic</span>
        <h1>Pet profiles</h1>
    </div>
    <div class="panel" id="list">
        <h2>Profiles</h2>
        @if($pets->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No pet profiles are available yet."
                body="When a pet profile is added, it will appear here."
            />
        @else
            <table>
                <thead><tr><th>Name</th><th>Species</th><th>Age</th><th>Sex</th><th></th></tr></thead>
                <tbody>
                @foreach($pets as $pet)
                    <tr>
                        <td>{{ $pet->name }}</td>
                        <td>{{ $pet->species }}</td>
                        <td>{{ $pet->age_years }}</td>
                        <td>{{ $pet->sex }}</td>
                        <td><a href="{{ route('petcare.pets.show', $pet) }}">Open</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $pets->links() }}
        @endif
    </div>
    @if($canCreatePet)
    <div class="panel" id="create">
        <h2>Add pet profile</h2>
        @if($returnTo)
            <p class="muted">After you save this pet, you will return to the form you started.</p>
        @endif
        <form method="post" action="{{ route('petcare.pets.store') }}" enctype="multipart/form-data">
            @csrf
            @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @endif
            <div class="row">
                <div><label>Name</label><input name="name"></div>
                <div><label>Species</label><input name="species"></div>
                <div><label>Age (years)</label><input type="number" min="0" max="80" name="age_years"></div>
            </div>
            <div class="row">
                <div><label>Sex</label><select name="sex">@foreach(['male','female','unknown'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                <div><label>Neutering status</label><select name="neutering_status">@foreach(['neutered','not_neutered','unknown'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                <div><label>Photo</label><input type="file" name="photo" accept="image/*"></div>
            </div>
            <label>Health issues</label>
            <textarea name="health_issues"></textarea>
            <button type="submit">Save pet profile</button>
        </form>
    </div>
    @endif
@endsection
