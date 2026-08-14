<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\CaseUpdate;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Notifications\ApesCicCaseUpdatedNotification;
use App\Notifications\ShelterCaseUpdatedNotification;
use App\Services\AuditLogger;
use App\Services\ModuleRouteContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseUpdateController extends Controller
{
    public function __construct(
        private readonly ModuleRouteContext $moduleContext,
    ) {}

    public function store(
        Request $request,
        ShelterCase $case,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        if ($instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE) {
            $case = ShelterCase::query()
                ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
                ->visibleTo($request->user(), ShelterCase::SUB_CORE_SHELTER_RESCUE)
                ->whereKey($case->getKey())
                ->whereHas(
                    'petProfile',
                    static fn ($pets) => $pets->where(
                        'service_domain',
                        PetProfile::DOMAIN_SHELTER,
                    ),
                )
                ->firstOrFail();
        } else {
            abort_unless(
                $case->sub_core_key === $instance->subCore->key,
                404,
            );
        }
        Gate::authorize('view', $case);
        Gate::authorize($prefix.'comment-own');

        if ($case->status === 'closed') {
            throw ValidationException::withMessages([
                'body' => 'Reopen the case before adding another update.',
            ]);
        }

        $validated = $request->validate([
            'body' => ['required', 'string'],
            'visibility' => [
                'nullable',
                Rule::in([
                    CaseUpdate::VISIBILITY_PUBLIC,
                    CaseUpdate::VISIBILITY_INTERNAL,
                ]),
            ],
        ]);
        $isStaffViewer = $request->user()->can($prefix.'view-all');
        $visibility = $isStaffViewer
            ? ($validated['visibility'] ?? CaseUpdate::VISIBILITY_PUBLIC)
            : CaseUpdate::VISIBILITY_PUBLIC;
        $case->updates()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'visibility' => $visibility,
        ]);
        if ($visibility === CaseUpdate::VISIBILITY_PUBLIC) {
            $case->touch();
        }

        $recipients = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission($prefix.'view-all')
            ->get();
        if ($visibility === CaseUpdate::VISIBILITY_PUBLIC
            && $case->user?->can('view', $case)) {
            $recipients->push($case->user);
        }
        if ($instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE
            && $visibility === CaseUpdate::VISIBILITY_INTERNAL) {
            $recipients = $recipients->reject(
                fn (User $recipient): bool => $recipient->id === $case->user_id,
            );
        }
        $recipients->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $request->user()->id)
            ->each(fn (User $recipient) => $recipient->notify(
                $instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE
                    ? new ShelterCaseUpdatedNotification(
                        $case,
                        $request->user(),
                        'updated',
                    )
                    : new ApesCicCaseUpdatedNotification(
                        $case,
                        $request->user(),
                        'updated',
                        $instance->subCore->key,
                        $this->moduleContext->showRouteName($instance),
                    ),
            ));

        $event = $instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE
            ? 'shelter.case.update_added'
            : 'apes_cic.case.update_added';
        $auditLogger->record($event, $request->user(), $case, [
            'sub_core_key' => $case->sub_core_key,
            'module_key' => $instance->module->key,
            'visibility' => $visibility,
        ]);

        return redirect()->route($this->moduleContext->showRouteName($instance), $case)
            ->with('status', 'Case update added.');
    }

    private function instance(Request $request): ModuleInstanceDefinition
    {
        return $this->moduleContext->resolve($request, 'cases');
    }
}
