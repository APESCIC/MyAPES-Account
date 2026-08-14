@extends('layouts.app')

@section('title', 'Shelter Pet Profiles')

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>Pet profiles</h1>
    </div>
    @if($canCreatePet)
        <div class="panel">
            <h2>Add pet profile</h2>
            <form method="post" action="{{ route('shelter.pets.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div><label for="pet_name">Name</label><input id="pet_name" name="name"></div>
                <div><label for="pet_species">Species</label><input id="pet_species" name="species"></div>
                <div><label for="pet_age_years">Age (years)</label><input id="pet_age_years" type="number" min="0" max="80" name="age_years"></div>
            </div>
            <div class="row">
                <div><label for="pet_sex">Sex</label><select id="pet_sex" name="sex">@foreach(['male','female','unknown'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                <div><label for="pet_neutering_status">Neutering status</label><select id="pet_neutering_status" name="neutering_status">@foreach(['neutered','not_neutered','unknown'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
                <div><label for="pet_photo">Photo</label><input id="pet_photo" type="file" name="photo" accept="image/*"></div>
            </div>
            <label for="pet_health_issues">Health issues</label>
            <textarea id="pet_health_issues" name="health_issues"></textarea>
            <button type="submit">Save pet profile</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Profiles</h2>
        <table>
            <thead><tr><th>Name</th><th>Species</th><th>Age</th><th>Sex</th><th></th></tr></thead>
            <tbody>
            @foreach($pets as $pet)
                <tr>
                    <td>{{ $pet->name }}</td>
                    <td>{{ $pet->species }}</td>
                    <td>{{ $pet->age_years }}</td>
                    <td>{{ $pet->sex }}</td>
                    <td><a href="{{ route('shelter.pets.show', $pet) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $pets->links() }}
    </div>
@endsection
