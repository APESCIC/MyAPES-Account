<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ModuleState;

class SupportTicketPolicy
{
    public function __construct(
        private readonly ModuleState $modules,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('apes-cic.tickets.view-own')
            || $user->can('apes-cic.tickets.view-all');
    }

    public function create(User $user): bool
    {
        return $user->can('apes-cic.tickets.create');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        $prefix = "{$ticket->sub_core_key}.tickets.";

        return $this->modules->enabled($ticket->sub_core_key, 'tickets')
            && ($user->can($prefix.'view-all')
                || ($ticket->user_id === $user->id
                    && $user->can($prefix.'view-own')));
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        $prefix = "{$ticket->sub_core_key}.tickets.";

        return $this->view($user, $ticket)
            && ($user->can($prefix.'update-all')
                || $user->can($prefix.'assign')
                || $user->can($prefix.'comment-own'));
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        $prefix = "{$ticket->sub_core_key}.tickets.";

        return $this->view($user, $ticket)
            && $user->can($prefix.'delete');
    }
}
