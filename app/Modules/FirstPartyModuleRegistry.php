<?php

namespace App\Modules;

use App\Contracts\ModuleRegistry;
use App\Modules\Activity\CaseRecentActivityProvider;
use App\Modules\Activity\PetCareConsultationRecentActivityProvider;
use App\Modules\Activity\PetProfileRecentActivityProvider;
use App\Modules\Activity\SupportTicketRecentActivityProvider;
use App\Modules\Analytics\CaseAnalyticsProvider;
use App\Modules\Analytics\PetCareConsultationAnalyticsProvider;
use App\Modules\Analytics\PetProfileAnalyticsProvider;
use App\Modules\Analytics\SupportTicketAnalyticsProvider;
use App\Modules\Attention\CaseAttentionProvider;
use App\Modules\Attention\ConsultationAttentionProvider;
use App\Modules\Attention\SupportTicketAttentionProvider;
use App\Modules\Detectors\PetCareConsultationActiveRecordDetector;
use App\Modules\Detectors\PetProfileActiveRecordDetector;
use App\Modules\Detectors\ShelterCaseActiveRecordDetector;
use App\Modules\Detectors\SupportTicketActiveRecordDetector;
use App\Modules\Summaries\PetCareConsultationSummaryProvider;
use App\Modules\Summaries\PetProfileSummaryProvider;
use App\Modules\Summaries\ShelterCaseSummaryProvider;
use App\Modules\Summaries\SupportTicketSummaryProvider;
use App\Services\ModuleRegistryValidator;
use InvalidArgumentException;

final class FirstPartyModuleRegistry implements ModuleRegistry
{
    /** @var array<string, SubCoreDefinition> */
    private array $subCores;

    /** @var array<string, ModuleDefinition> */
    private array $modules;

    /** @var array<string, ModuleInstanceDefinition> */
    private array $matrix;

    /** @var array<string, ModulePermissionDescriptor> */
    private array $permissions;

    public function __construct(ModuleRegistryValidator $validator)
    {
        $this->subCores = $this->buildSubCores();
        $this->modules = $this->buildModules();
        $this->matrix = $this->buildMatrix();
        $this->permissions = $this->buildPermissions();
        $validator->validate(
            $this->subCores,
            $this->modules,
            $this->matrix,
        );
    }

    public function subCores(): array
    {
        return $this->subCores;
    }

    public function modules(): array
    {
        return $this->modules;
    }

    public function matrix(): array
    {
        return array_values($this->matrix);
    }

    public function shippedInstances(): array
    {
        return array_filter(
            $this->matrix,
            static fn (ModuleInstanceDefinition $instance): bool => $instance->isShipped(),
        );
    }

    public function permissions(): array
    {
        return array_values($this->permissions);
    }

    public function subCore(string $key): SubCoreDefinition
    {
        return $this->subCores[$key]
            ?? throw new InvalidArgumentException('Unknown sub-core key.');
    }

    public function module(string $key): ModuleDefinition
    {
        return $this->modules[$key]
            ?? throw new InvalidArgumentException('Unknown module key.');
    }

    public function instance(
        string $subCoreKey,
        string $moduleKey,
    ): ModuleInstanceDefinition {
        return $this->matrix["{$subCoreKey}:{$moduleKey}"]
            ?? throw new InvalidArgumentException('Unknown module instance.');
    }

    public function recognizesPermission(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }

    public function permission(string $permission): ?ModulePermissionDescriptor
    {
        return $this->permissions[$permission] ?? null;
    }

    /** @return array<string, SubCoreDefinition> */
    private function buildSubCores(): array
    {
        $definitions = [
            new SubCoreDefinition(
                'apes-cic',
                'APES CIC',
                'Member support and organisation services.',
                '/apes-cic',
                'apes-cic.index',
                'building-2',
                10,
            ),
            new SubCoreDefinition(
                'shelter-rescue',
                'APES Shelter and Rescue',
                'Animal rescue, shelter and rehabilitation services.',
                '/shelter',
                'shelter.index',
                'house',
                20,
            ),
            new SubCoreDefinition(
                'pet-care-clinic',
                'APES Pet Care Clinic',
                'Pet care records and clinical consultations.',
                '/petcare',
                'petcare.index',
                'heart-pulse',
                30,
            ),
        ];

        $keyed = [];
        foreach ($definitions as $definition) {
            $keyed[$definition->key] = $definition;
        }
        ksort($keyed);

        return $keyed;
    }

    /** @return array<string, ModuleDefinition> */
    private function buildModules(): array
    {
        $publicRoles = [
            'service-user',
            'student',
            'volunteer',
            'staff',
            'administrator',
            'super-admin',
        ];
        $staffWorkRoles = [
            'student',
            'volunteer',
            'staff',
            'administrator',
            'super-admin',
        ];
        $staffDeleteRoles = [
            'staff',
            'administrator',
            'super-admin',
        ];
        $public = static fn (string $ability, string $label): ModuleAbilityDefinition => new ModuleAbilityDefinition(
            $ability,
            $label,
            false,
            $publicRoles,
        );
        $staff = static fn (string $ability, string $label): ModuleAbilityDefinition => new ModuleAbilityDefinition(
            $ability,
            $label,
            true,
            $staffWorkRoles,
        );
        $staffDelete = static fn (string $ability, string $label): ModuleAbilityDefinition => new ModuleAbilityDefinition(
            $ability,
            $label,
            true,
            $staffDeleteRoles,
        );

        $definitions = [
            new ModuleDefinition(
                'tickets',
                'Tickets',
                'Support requests and threaded responses.',
                '1.0.0',
                ['apes-cic', 'shelter-rescue', 'pet-care-clinic'],
                ['apes-cic', 'shelter-rescue', 'pet-care-clinic'],
                [
                    $public('view-own', 'View own tickets'),
                    $public('create', 'Create tickets'),
                    $public('comment-own', 'Comment on own tickets'),
                    $staff('view-all', 'View all tickets'),
                    $staff('update-all', 'Update all tickets'),
                    $staff('assign', 'Assign tickets'),
                    $staff('close', 'Close tickets'),
                    $staffDelete('delete', 'Delete tickets'),
                ],
                [
                    'apes-cic' => new ModuleNavigationDefinition(
                        'Tickets',
                        'apes-cic.tickets.index',
                        'ticket',
                        10,
                    ),
                    'shelter-rescue' => new ModuleNavigationDefinition(
                        'Tickets',
                        'shelter.tickets.index',
                        'ticket',
                        20,
                    ),
                    'pet-care-clinic' => new ModuleNavigationDefinition(
                        'Tickets',
                        'petcare.tickets.index',
                        'ticket',
                        20,
                    ),
                ],
                SupportTicketActiveRecordDetector::class,
                SupportTicketSummaryProvider::class,
                SupportTicketRecentActivityProvider::class,
                SupportTicketAnalyticsProvider::class,
                SupportTicketAttentionProvider::class,
            ),
            new ModuleDefinition(
                'cases',
                'Cases',
                'Rescue and welfare case records.',
                '1.0.0',
                ['apes-cic', 'shelter-rescue'],
                ['apes-cic', 'shelter-rescue'],
                [
                    $public('view-own', 'View own cases'),
                    $public('create', 'Create cases'),
                    $public('update-own', 'Update own cases'),
                    $public('comment-own', 'Comment on own cases'),
                    $staff('view-all', 'View all cases'),
                    $staff('update-all', 'Update all cases'),
                    $staff('assign', 'Assign cases'),
                    $staff('close', 'Close cases'),
                    $staffDelete('delete', 'Delete cases'),
                ],
                [
                    'apes-cic' => new ModuleNavigationDefinition(
                        'Cases',
                        'apes-cic.cases.index',
                        'briefcase-business',
                        20,
                    ),
                    'shelter-rescue' => new ModuleNavigationDefinition(
                        'Cases',
                        'shelter.cases.index',
                        'house',
                        30,
                    ),
                ],
                ShelterCaseActiveRecordDetector::class,
                ShelterCaseSummaryProvider::class,
                CaseRecentActivityProvider::class,
                CaseAnalyticsProvider::class,
                CaseAttentionProvider::class,
            ),
            new ModuleDefinition(
                'pet-profiles',
                'Pet Profiles',
                'Animal identity, care and welfare profiles.',
                '1.0.0',
                ['shelter-rescue', 'pet-care-clinic'],
                ['shelter-rescue', 'pet-care-clinic'],
                [
                    $public('view-own', 'View own pet profiles'),
                    $public('create', 'Create pet profiles'),
                    $public('update-own', 'Update own pet profiles'),
                    $staff('view-all', 'View all pet profiles'),
                    $staff('update-all', 'Update all pet profiles'),
                ],
                [
                    'shelter-rescue' => new ModuleNavigationDefinition(
                        'Pet Profiles',
                        'shelter.pets.index',
                        'paw-print',
                        10,
                    ),
                    'pet-care-clinic' => new ModuleNavigationDefinition(
                        'Pet Profiles',
                        'petcare.pets.index',
                        'paw-print',
                        10,
                    ),
                ],
                PetProfileActiveRecordDetector::class,
                PetProfileSummaryProvider::class,
            ),
            new ModuleDefinition(
                'consultations',
                'Consultations',
                'Pet care consultation records and follow-up.',
                '1.0.0',
                ['pet-care-clinic'],
                ['pet-care-clinic'],
                [
                    $public('view-own', 'View own consultations'),
                    $public('create', 'Create consultations'),
                    $public('update-own', 'Update own consultations'),
                    $staff('view-all', 'View all consultations'),
                    $staff('update-all', 'Update all consultations'),
                    $staff('assign', 'Assign consultations'),
                    $staff('close', 'Close consultations'),
                ],
                [
                    'pet-care-clinic' => new ModuleNavigationDefinition(
                        'Consultations',
                        'petcare.consultations.index',
                        'messages-square',
                        30,
                    ),
                ],
                PetCareConsultationActiveRecordDetector::class,
                PetCareConsultationSummaryProvider::class,
                PetCareConsultationRecentActivityProvider::class,
                PetCareConsultationAnalyticsProvider::class,
                attentionProvider: ConsultationAttentionProvider::class,
            ),
        ];

        $keyed = [];
        foreach ($definitions as $definition) {
            $keyed[$definition->key] = $definition;
        }
        ksort($keyed);

        return $keyed;
    }

    /** @return array<string, ModuleInstanceDefinition> */
    private function buildMatrix(): array
    {
        $matrix = [];

        foreach ($this->subCores as $subCore) {
            foreach ($this->modules as $module) {
                $compatible = in_array(
                    $subCore->key,
                    $module->compatibleSubCores,
                    true,
                );
                $shipped = in_array(
                    $subCore->key,
                    $module->shippedSubCores,
                    true,
                );
                $status = $shipped
                    ? ModuleCodeStatus::Shipped
                    : ($compatible
                        ? ModuleCodeStatus::CodeNotShipped
                        : ModuleCodeStatus::Incompatible);
                $dependencies = match ("{$subCore->key}:{$module->key}") {
                    'shelter-rescue:cases' => [
                        new ModuleDependency(
                            'shelter-rescue',
                            'pet-profiles',
                        ),
                    ],
                    'pet-care-clinic:consultations' => [
                        new ModuleDependency(
                            'pet-care-clinic',
                            'pet-profiles',
                        ),
                    ],
                    default => [],
                };
                $instance = new ModuleInstanceDefinition(
                    $subCore,
                    $module,
                    $status,
                    $dependencies,
                    recentActivityProvider: in_array(
                        "{$subCore->key}:{$module->key}",
                        [
                            'shelter-rescue:pet-profiles',
                            'pet-care-clinic:pet-profiles',
                        ],
                        true,
                    )
                        ? PetProfileRecentActivityProvider::class
                        : null,
                    analyticsProvider: in_array(
                        "{$subCore->key}:{$module->key}",
                        [
                            'shelter-rescue:pet-profiles',
                            'pet-care-clinic:pet-profiles',
                        ],
                        true,
                    )
                        ? PetProfileAnalyticsProvider::class
                        : null,
                );
                $matrix[$instance->key()] = $instance;
            }
        }

        ksort($matrix);

        return $matrix;
    }

    /** @return array<string, ModulePermissionDescriptor> */
    private function buildPermissions(): array
    {
        $permissions = [];

        foreach ($this->shippedInstances() as $instance) {
            foreach ($instance->module->abilities as $ability) {
                $name = "{$instance->subCore->key}.{$instance->module->key}.{$ability->ability}";
                $permissions[$name] = new ModulePermissionDescriptor(
                    $name,
                    $instance->subCore->key,
                    $instance->module->key,
                    $ability->ability,
                    $ability->label,
                    $ability->requiresDirectoryContext,
                    $ability->defaultRoles,
                );
            }
        }

        ksort($permissions);

        return $permissions;
    }
}
