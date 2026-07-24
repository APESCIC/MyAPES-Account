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

        return view('dashboard', [
            'ticketCount' => $ticketQuery->count(),
            'shelterCaseCount' => $shelterCasesQuery->count(),
            'consultationCount' => $consultationsQuery->count(),
            'petProfileCount' => $petProfilesQuery->count(),
        ]);
    }
}
