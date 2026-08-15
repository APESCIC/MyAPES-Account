<?php

namespace App\Modules\Attention;

use App\Contracts\ModuleAttentionProvider;
use App\Models\PetCareConsultation;
use App\Models\User;
use App\Modules\ModuleAttentionItem;
use App\Modules\ModuleInstanceDefinition;

class ConsultationAttentionProvider implements ModuleAttentionProvider
{
    public function attention(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 6,
    ): array {
        return PetCareConsultation::query()
            ->forPetCareDomain()
            ->visibleTo($user)
            ->where('status', '<>', 'closed')
            ->whereNull('closed_at')
            ->with(['petProfile', 'user'])
            ->latest('updated_at')
            ->limit(max(0, $limit))
            ->get()
            ->map(fn (PetCareConsultation $consultation): ModuleAttentionItem => new ModuleAttentionItem(
                $instance->key(),
                'consultation',
                'messages-square',
                'APES Pet Care Clinic',
                'Consultation',
                $consultation->subject,
                $consultation->status,
                null,
                $consultation->scheduled_for
                    ? 'Scheduled '.$consultation->scheduled_for->diffForHumans()
                    : null,
                $consultation->petProfile
                    ? "Pet: {$consultation->petProfile->name}"
                    : null,
                $consultation->updated_at,
                'petcare.consultations.show',
                $consultation->id,
            ))
            ->all();
    }
}
