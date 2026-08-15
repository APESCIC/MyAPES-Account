<?php

namespace App\Services;

use InvalidArgumentException;

final class TicketServiceConfiguration
{
    public function for(string $subCoreKey): TicketServiceDefinition
    {
        return match ($subCoreKey) {
            'apes-cic' => new TicketServiceDefinition(
                'APES CIC',
                'apes-cic.tickets',
                'apes_cic.ticket',
                'apes-cic',
                'Organisational support tickets',
                'Service support for legal, human resources, IT, web development and related needs.',
                ['legal', 'human_resources', 'it', 'web_dev', 'operations', 'other'],
                true,
            ),
            'shelter-rescue' => new TicketServiceDefinition(
                'APES Shelter and Rescue',
                'shelter.tickets',
                'shelter.ticket',
                'apes-shelter',
                'APES Shelter and Rescue Tickets',
                'Support for adoption, surrender, rescue, fostering and animal welfare.',
                ['adoption', 'surrender', 'rescue', 'fostering', 'animal_welfare', 'other'],
                false,
            ),
            'pet-care-clinic' => new TicketServiceDefinition(
                'APES Pet Care Clinic',
                'petcare.tickets',
                'petcare.ticket',
                'apes-petcare',
                'APES Pet Care Clinic Tickets',
                'Support for appointments, consultations, prescriptions, billing and follow-up.',
                ['appointment', 'consultation', 'prescription', 'billing', 'follow_up', 'other'],
                false,
            ),
            default => throw new InvalidArgumentException('Unknown ticket service.'),
        };
    }
}
