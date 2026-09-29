<div class="empty-pet-select" data-empty-pet-select>
    <p class="muted">{{ __('pet_profiles::ui.staff-empty-pet-select.blade.no_pet_profiles_are_available_yet') }}</p>
    @if($addPetUrl)
        <p>
            <a href="{{ $addPetUrl }}" data-add-pet-first>{{ __('pet_profiles::ui.staff-empty-pet-select.blade.add_a_pet_first') }}</a>
            then return here to continue this form.
        </p>
    @else
        <p class="muted">{{ __('pet_profiles::ui.staff-empty-pet-select.blade.a_pet_profile_is_required_before_you_can_continue_this_') }}</p>
    @endif
</div>
