<?php

namespace App\Http\Controllers;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        $ticketQuery = SupportTicket::query();
        $shelterCasesQuery = ShelterCase::query();
        $consultationsQuery = PetCareConsultation::query();
        $petProfilesQuery = PetProfile::query();

        if (! $user->isStaff()) {
            $ticketQuery->where('user_id', $user->id);
            $shelterCasesQuery->where('user_id', $user->id);
            $consultationsQuery->where('user_id', $user->id);
            $petProfilesQuery->where('user_id', $user->id);
        }

        $openTicketQuery = (clone $ticketQuery)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['resolved', 'closed']);
        $openShelterCaseQuery = (clone $shelterCasesQuery)
            ->whereNull('closed_at')
            ->where('status', '!=', 'closed');
        $openConsultationQuery = (clone $consultationsQuery)
            ->whereNull('closed_at')
            ->where('status', '!=', 'closed');

        $attentionItems = collect()
            ->concat(
                (clone $openTicketQuery)
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
                        'owner' => $ticket->user ? "From: {$ticket->user->name}" : null,
                        'updatedAt' => $ticket->updated_at,
                        'url' => route('apes-cic.tickets.show', $ticket),
                    ])
            )
            ->concat(
                (clone $openShelterCaseQuery)
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
                        'context' => str($case->case_type)->replace('_', ' ')->title()->toString(),
                        'owner' => $case->petProfile ? "Pet: {$case->petProfile->name}" : null,
                        'updatedAt' => $case->updated_at,
                        'url' => route('shelter.cases.show', $case),
                    ])
            )
            ->concat(
                (clone $openConsultationQuery)
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
                            ? 'Scheduled '.$consultation->scheduled_for->diffForHumans()
                            : null,
                        'owner' => $consultation->petProfile ? "Pet: {$consultation->petProfile->name}" : null,
                        'updatedAt' => $consultation->updated_at,
                        'url' => route('petcare.consultations.show', $consultation),
                    ])
            )
            ->sortByDesc(fn (array $item): int => $item['updatedAt']->getTimestamp())
            ->take(6)
            ->values()
            ->all();

        return view('dashboard', [
            'ticketCount' => (clone $ticketQuery)->count(),
            'openTicketCount' => (clone $openTicketQuery)->count(),
            'highPriorityTicketCount' => (clone $openTicketQuery)
                ->whereIn('priority', ['high', 'urgent'])
                ->count(),
            'shelterCaseCount' => (clone $shelterCasesQuery)->count(),
            'openShelterCaseCount' => (clone $openShelterCaseQuery)->count(),
            'consultationCount' => (clone $consultationsQuery)->count(),
            'openConsultationCount' => (clone $openConsultationQuery)->count(),
            'petProfileCount' => (clone $petProfilesQuery)->count(),
            'attentionItems' => $attentionItems,
        ]);
    }
}
