<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuthorizationProfile;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $this->hasStaffPermission($user)
            || $ticket->user_id === $user->id;
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $this->hasStaffPermission($user);
    }

    private function hasStaffPermission(User $user): bool
    {
        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS);
    }
}
