<div class="empty-pet-select" data-empty-pet-select>
    <p class="muted">No pet profiles are available yet.</p>
    @if($addPetUrl)
        <p>
            <a href="{{ $addPetUrl }}" data-add-pet-first>Add a pet first</a>
            then return here to continue this form.
        </p>
    @else
        <p class="muted">A pet profile is required before you can continue this form.</p>
    @endif
</div>
