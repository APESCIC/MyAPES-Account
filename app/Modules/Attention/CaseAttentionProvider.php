<?php

namespace App\Modules\Attention;

use App\Contracts\ModuleAttentionProvider;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Modules\ModuleAttentionItem;
use App\Modules\ModuleInstanceDefinition;

class CaseAttentionProvider implements ModuleAttentionProvider
{
    public function attention(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 6,
    ): array {
        $isApesCic = $instance->subCore->key
            === ShelterCase::SUB_CORE_APES_CIC;
        $query = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key);

        if ($isApesCic) {
            $query->whereNotIn('status', ['resolved', 'closed']);
        } else {
            $query
                ->where('status', '<>', 'closed')
                ->whereHas(
                    'petProfile',
                    static fn ($pets) => $pets->where(
                        'service_domain',
                        PetProfile::DOMAIN_SHELTER,
                    ),
                );
        }

        return $query
            ->with(['petProfile', 'user'])
            ->latest('updated_at')
            ->limit(max(0, $limit))
            ->get()
            ->map(fn (ShelterCase $case): ModuleAttentionItem => new ModuleAttentionItem(
                $instance->key(),
                $isApesCic ? 'ticket' : 'shelter',
                $isApesCic ? 'briefcase-business' : 'house',
                $isApesCic ? 'APES CIC' : 'APES Shelter',
                'Case',
                $case->title,
                $case->status,
                $isApesCic ? $case->priority : null,
                str($isApesCic ? $case->category : $case->case_type)
                    ->replace('_', ' ')
                    ->title()
                    ->toString(),
                $isApesCic
                    ? ($case->user ? "For: {$case->user->name}" : null)
                    : ($case->petProfile
                        ? "Pet: {$case->petProfile->name}"
                        : null),
                $case->updated_at,
                $isApesCic
                    ? 'apes-cic.cases.show'
                    : 'shelter.cases.show',
                $case->id,
            ))
            ->all();
    }
}
