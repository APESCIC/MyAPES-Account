# Pet Profiles plugin

Shared animal identity plugin for **Pet Care Clinic** and **Shelter and Rescue**.

- Live URL segment: `/pets` under `/petcare` and `/shelter`
- One controller + view set (`Plugins\PetProfiles`) driven by `ModuleContext`
- Public API: `PetProfilesContract` for Cases / Consultations selectors
- Morph alias: `pet_profile` → `Plugins\PetProfiles\Models\PetProfile`
- Historical migration remains in `database/migrations/` (forward-only policy)

See issue #291.
