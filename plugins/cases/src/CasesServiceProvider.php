<?php

namespace Plugins\Cases;

use App\Core\Extensions\Plugins\PluginAbilityFactory;
use App\Core\Extensions\Plugins\PluginDependency;
use App\Core\Extensions\Plugins\PluginManifest;
use App\Core\Extensions\Plugins\PluginNavigationItem;
use App\Core\Extensions\Plugins\PluginServiceProvider;
use App\Core\Extensions\Plugins\PluginSettingsSchema;
use Illuminate\Support\Facades\Gate;
use Plugins\Cases\Dashboard\CaseAnalyticsProvider;
use Plugins\Cases\Dashboard\CaseAttentionProvider;
use Plugins\Cases\Dashboard\CaseRecentActivityProvider;
use Plugins\Cases\Dashboard\ShelterCaseActiveRecordDetector;
use Plugins\Cases\Dashboard\ShelterCaseSummaryProvider;
use Plugins\Cases\Models\ShelterCase;
use Plugins\Cases\Policies\ShelterCasePolicy;

class CasesServiceProvider extends PluginServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cases');

        Gate::policy(ShelterCase::class, ShelterCasePolicy::class);
    }

    protected function manifest(): PluginManifest
    {
        $a = PluginAbilityFactory::class;

        return PluginManifest::make(
            slug: 'cases',
            name: 'Cases',
            description: 'Rescue and welfare case records.',
            version: '1.0.0',
            requiresCore: '^0.37.0',
            compatibleModules: ['apes-cic', 'shelter-rescue'],
            shippedModules: ['apes-cic', 'shelter-rescue'],
            permissions: [
                $a::public('view-own', 'View own cases'),
                $a::public('create', 'Create cases'),
                $a::public('update-own', 'Update own cases'),
                $a::public('comment-own', 'Comment on own cases'),
                $a::staff('view-all', 'View all cases'),
                $a::staff('update-all', 'Update all cases'),
                $a::staff('assign', 'Assign cases'),
                $a::staff('close', 'Close cases'),
                $a::staffDelete('delete', 'Delete cases'),
            ],
            // Shelter cases need Pet Profiles; APES CIC cases do not (#286 / #289).
            dependencies: [
                new PluginDependency('pet-profiles', onlyModules: ['shelter-rescue']),
            ],
            navigation: [
                new PluginNavigationItem('Cases', 'apes-cic.cases.index', 'briefcase-business', 20, moduleSlug: 'apes-cic'),
                new PluginNavigationItem('Cases', 'shelter.cases.index', 'house', 30, moduleSlug: 'shelter-rescue'),
            ],
            settingsByModule: [
                'apes-cic' => new PluginSettingsSchema(
                    supportsSettings: true,
                    schema: PluginSettingsSchema::SCHEMA_WEBSITES_CATEGORIES,
                    groupKey: 'categories',
                    groupLabel: 'Case categories',
                ),
                'shelter-rescue' => new PluginSettingsSchema(supportsSettings: false),
            ],
            translationNamespace: 'cases',
            searchKeywordsKey: 'cases::plugin.keywords',
            activeRecordDetector: ShelterCaseActiveRecordDetector::class,
            summaryProvider: ShelterCaseSummaryProvider::class,
            recentActivityProvider: CaseRecentActivityProvider::class,
            analyticsProvider: CaseAnalyticsProvider::class,
            attentionProvider: CaseAttentionProvider::class,
            staffRouteFiles: [
                dirname(__DIR__).'/routes/apes-cic.php',
                dirname(__DIR__).'/routes/shelter.php',
            ],
        );
    }
}
