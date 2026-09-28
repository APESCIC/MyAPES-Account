<?php

namespace Plugins\Recruitment;

use App\Core\Extensions\Navigation\PublicNavigationItem;
use App\Core\Extensions\Navigation\PublicNavigationRegistry;
use App\Core\Extensions\Navigation\StaffPluginNavigationItem;
use App\Core\Extensions\Navigation\StaffPluginNavigationRegistry;
use App\Core\Extensions\Plugins\PluginAbilityFactory;
use App\Core\Extensions\Plugins\PluginManifest;
use App\Core\Extensions\Plugins\PluginNavigationItem;
use App\Core\Extensions\Plugins\PluginServiceProvider;
use App\Core\Extensions\Plugins\PluginSettingsSchema;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRole;
use App\Modules\Detectors\RecruitmentActiveRecordDetector;
use App\Policies\RecruitmentApplicationPolicy;
use App\Policies\RecruitmentRolePolicy;
use App\Services\ModuleCatalogueProjection;
use App\Services\ModuleSettingsService;
use Illuminate\Support\Facades\Gate;

class RecruitmentServiceProvider extends PluginServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(RecruitmentRole::class, RecruitmentRolePolicy::class);
        Gate::policy(RecruitmentApplication::class, RecruitmentApplicationPolicy::class);

        $this->registerPublicNavigation();
        $this->registerStaffNavigation();
    }

    protected function manifest(): PluginManifest
    {
        $a = PluginAbilityFactory::class;

        return PluginManifest::make(
            slug: 'recruitment',
            name: 'Recruitment',
            description: 'Staff, volunteering, and student roles for APES CIC.',
            version: '1.0.0',
            requiresCore: '^0.37.0',
            compatibleModules: ['apes-cic'],
            shippedModules: ['apes-cic'],
            permissions: [
                $a::public('view-own', 'View own recruitment items'),
                $a::staff('create', 'Create recruitment roles'),
                $a::staff('view-all', 'View all recruitment roles'),
                $a::staff('update', 'Update recruitment roles'),
                $a::staffDelete('delete', 'Delete recruitment roles'),
                $a::staff('review-applications', 'Review recruitment applications'),
            ],
            navigation: [
                new PluginNavigationItem(
                    'Recruitment',
                    'apes-cic.recruitment.index',
                    'clipboard-list',
                    30,
                    moduleSlug: 'apes-cic',
                ),
            ],
            settingsByModule: [
                'apes-cic' => new PluginSettingsSchema(
                    supportsSettings: true,
                    schema: PluginSettingsSchema::SCHEMA_RECRUITMENT_BOARD,
                ),
            ],
            translationNamespace: 'recruitment',
            searchKeywordsKey: 'recruitment::plugin.keywords',
            activeRecordDetector: RecruitmentActiveRecordDetector::class,
            publicRouteFiles: [],
        );
    }

    private function registerPublicNavigation(): void
    {
        app(PublicNavigationRegistry::class)->contribute(function (): array {
            $enabled = false;

            try {
                $enabled = app(ModuleSettingsService::class)->recruitmentPublicBoardEnabled();
            } catch (\Throwable) {
                $enabled = false;
            }

            return [
                new PublicNavigationItem(
                    label: 'Recruitment',
                    routeName: 'recruitment.index',
                    icon: 'briefcase',
                    order: 40,
                    routeIsPattern: 'recruitment.*',
                    enabled: $enabled,
                ),
            ];
        });
    }

    private function registerStaffNavigation(): void
    {
        app(StaffPluginNavigationRegistry::class)->contribute(function (): array {
            $enabled = false;

            try {
                $enabled = in_array(
                    'apes-cic:recruitment',
                    app(ModuleCatalogueProjection::class)->enabledInstanceKeys(),
                    true,
                );
            } catch (\Throwable) {
                $enabled = false;
            }

            return [
                new StaffPluginNavigationItem(
                    label: 'Recruit manage',
                    icon: 'clipboard-list',
                    order: 35,
                    routeIsPattern: 'apes-cic.recruitment.*',
                    abilities: [
                        'apes-cic.recruitment.view-all',
                        'apes-cic.recruitment.create',
                        'apes-cic.recruitment.update',
                        'apes-cic.recruitment.delete',
                        'apes-cic.recruitment.review-applications',
                    ],
                    enabled: $enabled,
                    homeRouteName: 'apes-cic.recruitment.index',
                    fallbackRouteName: 'apes-cic.recruitment.applications.index',
                    homeAbility: 'apes-cic.recruitment.view-all',
                ),
            ];
        });
    }
}
