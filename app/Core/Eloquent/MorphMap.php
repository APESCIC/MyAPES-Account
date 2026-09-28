<?php

namespace App\Core\Eloquent;

use App\Core\Accounts\DirectoryGroup;
use App\Core\Accounts\DirectoryGroupRoleMapping;
use App\Core\Accounts\Permission;
use App\Core\Accounts\Role;
use App\Core\Accounts\StaffProfile;
use App\Core\Accounts\User;
use App\Core\Accounts\UserProfile;
use App\Core\Attachments\SupportAttachment;
use App\Core\Extensions\Models\ModuleInstallation;
use App\Core\Maintenance\MaintenanceWindow;
use App\Models\CaseUpdate;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRole;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Stable morph aliases for polymorphic columns (#281).
 *
 * Aliases stay fixed when models later move into Core / Modules / Plugins namespaces.
 */
final class MorphMap
{
    /**
     * @return array<string, class-string>
     */
    public static function aliases(): array
    {
        return [
            'user' => User::class,
            'user_profile' => UserProfile::class,
            'staff_profile' => StaffProfile::class,
            'support_ticket' => SupportTicket::class,
            'support_ticket_message' => SupportTicketMessage::class,
            'support_attachment' => SupportAttachment::class,
            'case' => ShelterCase::class,
            'case_update' => CaseUpdate::class,
            'pet_profile' => PetProfile::class,
            'consultation' => PetCareConsultation::class,
            'recruitment_role' => RecruitmentRole::class,
            'recruitment_application' => RecruitmentApplication::class,
            'maintenance_window' => MaintenanceWindow::class,
            'role' => Role::class,
            'permission' => Permission::class,
            'directory_group' => DirectoryGroup::class,
            'directory_group_role_mapping' => DirectoryGroupRoleMapping::class,
            'module_installation' => ModuleInstallation::class,
        ];
    }

    public static function enforce(): void
    {
        Relation::enforceMorphMap(self::aliases());
    }

    /**
     * @param  class-string  $class
     */
    public static function aliasFor(string $class): string
    {
        $alias = array_search($class, self::aliases(), true);

        if ($alias === false) {
            throw new \InvalidArgumentException("No morph alias registered for [{$class}].");
        }

        return $alias;
    }
}
