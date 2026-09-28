<div>
    <label>Pet profile</label>
    <select name="pet_profile_id">
        @foreach($petProfiles as $petProfile)
            <option value="{{ $petProfile->id }}" @selected((string) old('pet_profile_id', request('pet_profile_id')) === (string) $petProfile->id)>{{ $petProfile->name }}</option>
        @endforeach
    </select>
</div>
