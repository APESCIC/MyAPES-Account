<?php

namespace Plugins\Consultations;

use App\Core\Extensions\Plugins\PluginAbilityFactory;
use App\Core\Extensions\Plugins\PluginDependency;
use App\Core\Extensions\Plugins\PluginManifest;
use App\Core\Extensions\Plugins\PluginNavigationItem;
use App\Core\Extensions\Plugins\PluginServiceProvider;
use App\Core\Extensions\Plugins\PluginSettingsSchema;
use App\Models\PetCareConsultation;
use App\Modules\Activity\PetCareConsultationRecentActivityProvider;
use App\Modules\Analytics\PetCareConsultationAnalyticsProvider;
use App\Modules\Attention\ConsultationAttentionProvider;
use App\Modules\Detectors\PetCareConsultationActiveRecordDetector;
use App\Modules\Summaries\PetCareConsultationSummaryProvider;
use App\Policies\PetCareConsultationPolicy;
use Illuminate\Support\Facades\Gate;

class ConsultationsServiceProvider extends PluginServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(PetCareConsultation::class, PetCareConsultationPolicy::class);
    }

    protected function manifest(): PluginManifest
    {
        $a = PluginAbilityFactory::class;

        return PluginManifest::make(
            slug: 'consultations',
            name: 'Consultations',
            description: 'Pet care consultation records and follow-up.',
            version: '1.0.0',
            requiresCore: '^0.37.0',
            compatibleModules: ['pet-care-clinic'],
            shippedModules: ['pet-care-clinic'],
            permissions: [
                $a::public('view-own', 'View own consultations'),
                $a::public('create', 'Create consultations'),
                $a::public('update-own', 'Update own consultations'),
                $a::staff('view-all', 'View all consultations'),
                $a::staff('update-all', 'Update all consultations'),
                $a::staff('assign', 'Assign consultations'),
                $a::staff('close', 'Close consultations'),
            ],
            dependencies: [
                new PluginDependency('pet-profiles'),
            ],
            navigation: [
                new PluginNavigationItem(
                    'Consultations',
                    'petcare.consultations.index',
                    'messages-square',
                    30,
                    moduleSlug: 'pet-care-clinic',
                ),
            ],
            settingsByModule: [
                'pet-care-clinic' => new PluginSettingsSchema(supportsSettings: false),
            ],
            translationNamespace: 'consultations',
            searchKeywordsKey: 'consultations::plugin.keywords',
            activeRecordDetector: PetCareConsultationActiveRecordDetector::class,
            summaryProvider: PetCareConsultationSummaryProvider::class,
            recentActivityProvider: PetCareConsultationRecentActivityProvider::class,
            analyticsProvider: PetCareConsultationAnalyticsProvider::class,
            attentionProvider: ConsultationAttentionProvider::class,
        );
    }
}
