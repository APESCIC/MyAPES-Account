<?php

namespace App\Support;

final class MascotTips
{
    /**
     * @return array{title: string, body: string}|null
     */
    public function forRoute(?string $routeName): ?array
    {
        if ($routeName === null || str_starts_with($routeName, 'admin.') || $routeName === 'change-log.index') {
            return null;
        }

        return match ($routeName) {
            'home' => [
                'title' => 'Pick the door that matches you.',
                'body' => 'Public Login is for service users. Staff and administrators should use Staff Login.',
            ],
            'public.login' => [
                'title' => 'Use your public account.',
                'body' => 'Sign in with your username or email to reach your profile, pets, and support.',
            ],
            'public.register' => [
                'title' => 'Tick the services you need.',
                'body' => 'Choose at least one MyAPES service. You can update these later in Profile.',
            ],
            'staff.login' => [
                'title' => 'Staff use Cloudron.',
                'body' => 'APES staff and administrators sign in through APES Cloudron. Local QA can use seeded credentials.',
            ],
            'verification.notice' => [
                'title' => 'Check the signed email link.',
                'body' => 'Open the verification link before setup. You can send another copy if you cannot find the first.',
            ],
            'onboarding.edit' => [
                'title' => 'Finish the essentials.',
                'body' => 'Confirm your UK contact details and services so MyAPES can route the right tools to you.',
            ],
            'dashboard' => [
                'title' => 'Start with what needs you.',
                'body' => 'Open items appear on the right. Service totals sit below when support services are available.',
            ],
            'apes-cic.index', 'shelter.index', 'petcare.index' => [
                'title' => 'Pick a support tool below.',
                'body' => 'Support tools for this service appear here. If the list is empty, nothing is available to you yet.',
            ],
            'apes-cic.tickets.index', 'shelter.tickets.index', 'petcare.tickets.index' => [
                'title' => 'Describe the need clearly.',
                'body' => 'Choose a service area and priority, then write a subject staff can act on.',
            ],
            'apes-cic.cases.index' => [
                'title' => 'Cases are permission-scoped.',
                'body' => 'You only see cases available to you. Open one if you can create, or wait until a case is shared.',
            ],
            default => [
                'title' => 'Need a pointer?',
                'body' => 'Use the sidebar to move between Dashboard, Profile, and your services.',
            ],
        };
    }

    /**
     * @return array{title: string, body: string, route: string}|null
     */
    public function forCurrentRequest(): ?array
    {
        $routeName = request()->route()?->getName();
        $tip = $this->forRoute($routeName);

        if ($tip === null || $routeName === null) {
            return null;
        }

        return [
            ...$tip,
            'route' => $routeName,
        ];
    }
}
