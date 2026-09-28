<?php

namespace Plugins\Tickets;

use App\Core\Extensions\Plugins\PluginAbilityFactory;
use App\Core\Extensions\Plugins\PluginManifest;
use App\Core\Extensions\Plugins\PluginNavigationItem;
use App\Core\Extensions\Plugins\PluginServiceProvider;
use App\Core\Extensions\Plugins\PluginSettingsSchema;
use App\Models\SupportTicket;
use App\Modules\Activity\SupportTicketRecentActivityProvider;
use App\Modules\Analytics\SupportTicketAnalyticsProvider;
use App\Modules\Attention\SupportTicketAttentionProvider;
use App\Modules\Detectors\SupportTicketActiveRecordDetector;
use App\Modules\Summaries\SupportTicketSummaryProvider;
use App\Policies\SupportTicketPolicy;
use Illuminate\Support\Facades\Gate;

class TicketsServiceProvider extends PluginServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
    }

    protected function manifest(): PluginManifest
    {
        $a = PluginAbilityFactory::class;

        return PluginManifest::make(
            slug: 'tickets',
            name: 'Tickets',
            description: 'Support requests and threaded responses.',
            version: '1.0.0',
            requiresCore: '^0.37.0',
            compatibleModules: ['apes-cic', 'shelter-rescue', 'pet-care-clinic'],
            shippedModules: ['apes-cic', 'shelter-rescue', 'pet-care-clinic'],
            permissions: [
                $a::public('view-own', 'View own tickets'),
                $a::public('create', 'Create tickets'),
                $a::public('comment-own', 'Comment on own tickets'),
                $a::staff('view-all', 'View all tickets'),
                $a::staff('update-all', 'Update all tickets'),
                $a::staff('assign', 'Assign tickets'),
                $a::staff('close', 'Close tickets'),
                $a::staffDelete('delete', 'Delete tickets'),
            ],
            navigation: [
                new PluginNavigationItem('Tickets', 'apes-cic.tickets.index', 'ticket', 10, moduleSlug: 'apes-cic'),
                new PluginNavigationItem('Tickets', 'shelter.tickets.index', 'ticket', 20, moduleSlug: 'shelter-rescue'),
                new PluginNavigationItem('Tickets', 'petcare.tickets.index', 'ticket', 20, moduleSlug: 'pet-care-clinic'),
            ],
            settingsByModule: [
                'apes-cic' => new PluginSettingsSchema(
                    supportsSettings: true,
                    schema: PluginSettingsSchema::SCHEMA_WEBSITES_CATEGORIES,
                    groupKey: 'service_areas',
                    groupLabel: 'Service areas',
                ),
                'shelter-rescue' => new PluginSettingsSchema(supportsSettings: false),
                'pet-care-clinic' => new PluginSettingsSchema(supportsSettings: false),
            ],
            translationNamespace: 'tickets',
            searchKeywordsKey: 'tickets::plugin.keywords',
            activeRecordDetector: SupportTicketActiveRecordDetector::class,
            summaryProvider: SupportTicketSummaryProvider::class,
            recentActivityProvider: SupportTicketRecentActivityProvider::class,
            analyticsProvider: SupportTicketAnalyticsProvider::class,
            attentionProvider: SupportTicketAttentionProvider::class,
        );
    }
}
