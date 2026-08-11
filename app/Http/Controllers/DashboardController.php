<?php

namespace App\Http\Controllers;

use App\Models\PetCareConsultation;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Services\ModuleDashboardSummaryService;
use App\Services\ModuleState;
use App\Services\ServiceEntitlement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(
        ModuleDashboardSummaryService $summaries,
        ModuleState $modules,
        ServiceEntitlement $entitlement,
    ): View {
        $user = request()->user();
        $attentionItems = collect();

        if ($entitlement->allows($user, 'apes-cic', request())
            && $modules->enabled('apes-cic', 'tickets')
            && ($user->can('apes-cic.tickets.view-own')
                || $user->can('apes-cic.tickets.view-all'))) {
            $openTickets = SupportTicket::query()
                ->forSubCore(SupportTicket::SUB_CORE_APES_CIC)
                ->visibleTo($user, SupportTicket::SUB_CORE_APES_CIC)
                ->whereNull('closed_at')
                ->whereNotIn('status', ['resolved', 'closed']);
            $attentionItems = $attentionItems->concat(
                (clone $openTickets)
                    ->with('user')
                    ->get()
                    ->map(fn (SupportTicket $ticket): array => [
                        'type' => 'ticket',
                        'icon' => 'ticket',
                        'service' => 'APES CIC',
                        'label' => 'Ticket',
                        'title' => $ticket->subject,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'context' => null,
                        'owner' => $ticket->user
                            ? "From: {$ticket->user->name}"
                            : null,
                        'updatedAt' => $ticket->updated_at,
                        'url' => route('apes-cic.tickets.show', $ticket),
                    ]),
            );
        }

        if ($entitlement->allows($user, 'apes-cic', request())
            && $modules->enabled('apes-cic', 'cases')
            && ($user->can('apes-cic.cases.view-own')
                || $user->can('apes-cic.cases.view-all'))) {
            $openCases = ShelterCase::query()
                ->forSubCore(ShelterCase::SUB_CORE_APES_CIC)
                ->visibleTo($user, ShelterCase::SUB_CORE_APES_CIC)
                ->whereNull('closed_at')
                ->whereNotIn('status', ['resolved', 'closed']);
            $attentionItems = $attentionItems->concat(
                (clone $openCases)
                    ->with('user')
                    ->get()
                    ->map(fn (ShelterCase $case): array => [
                        'type' => 'ticket',
                        'icon' => 'briefcase-business',
                        'service' => 'APES CIC',
                        'label' => 'Case',
                        'title' => $case->title,
                        'status' => $case->status,
                        'priority' => $case->priority,
                        'context' => str($case->category)
                            ->replace('_', ' ')
                            ->title()
                            ->toString(),
                        'owner' => $case->user
                            ? "For: {$case->user->name}"
                            : null,
                        'updatedAt' => $case->updated_at,
                        'url' => route('apes-cic.cases.show', $case),
                    ]),
            );
        }

        if ($entitlement->allows($user, 'shelter-rescue', request())
            && $modules->enabled('shelter-rescue', 'cases')) {
            $openCases = ShelterCase::query()
                ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
                ->visibleTo($user)
                ->whereNull('closed_at')
                ->where('status', '<>', 'closed');
            $attentionItems = $attentionItems->concat(
                (clone $openCases)
                    ->with(['petProfile', 'user'])
                    ->get()
                    ->map(fn (ShelterCase $case): array => [
                        'type' => 'shelter',
                        'icon' => 'house',
                        'service' => 'APES Shelter',
                        'label' => 'Case',
                        'title' => $case->title,
                        'status' => $case->status,
                        'priority' => null,
                        'context' => str($case->case_type)
                            ->replace('_', ' ')
                            ->title()
                            ->toString(),
                        'owner' => $case->petProfile
                            ? "Pet: {$case->petProfile->name}"
                            : null,
                        'updatedAt' => $case->updated_at,
                        'url' => route('shelter.cases.show', $case),
                    ]),
            );
        }

        if ($entitlement->allows($user, 'pet-care-clinic', request())
            && $modules->enabled('pet-care-clinic', 'consultations')) {
            $openConsultations = PetCareConsultation::query()
                ->visibleTo($user)
                ->whereNull('closed_at')
                ->where('status', '<>', 'closed');
            $attentionItems = $attentionItems->concat(
                (clone $openConsultations)
                    ->with(['petProfile', 'user'])
                    ->get()
                    ->map(fn (PetCareConsultation $consultation): array => [
                        'type' => 'consultation',
                        'icon' => 'messages-square',
                        'service' => 'APES Pet Care',
                        'label' => 'Consultation',
                        'title' => $consultation->subject,
                        'status' => $consultation->status,
                        'priority' => null,
                        'context' => $consultation->scheduled_for
                            ? 'Scheduled '.$consultation->scheduled_for
                                ->diffForHumans()
                            : null,
                        'owner' => $consultation->petProfile
                            ? "Pet: {$consultation->petProfile->name}"
                            : null,
                        'updatedAt' => $consultation->updated_at,
                        'url' => route(
                            'petcare.consultations.show',
                            $consultation,
                        ),
                    ]),
            );
        }

        return view('dashboard', [
            'moduleSummaries' => $summaries->forUser($user),
            'attentionItems' => $this->sortedAttention($attentionItems),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function sortedAttention(Collection $items): array
    {
        return $items
            ->sortByDesc(
                static fn (array $item): int => $item['updatedAt']
                    ->getTimestamp(),
            )
            ->take(6)
            ->values()
            ->all();
    }
}
