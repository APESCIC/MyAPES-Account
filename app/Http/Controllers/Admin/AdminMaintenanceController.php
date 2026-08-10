<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MaintenanceTransitionException;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\MaintenanceLifecycleManager;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminMaintenanceController extends Controller
{
    public function index(MaintenanceLifecycleManager $maintenance): View
    {
        return view('admin.maintenance.index', $maintenance->status());
    }

    public function activate(
        Request $request,
        MaintenanceLifecycleManager $maintenance,
        AuditLogger $audit,
    ): RedirectResponse {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:500'],
            'planned_end_at' => ['nullable', 'date', 'after:now'],
            'confirm_activation' => ['accepted'],
        ]);

        if ($validator->fails()) {
            $audit->record('maintenance.activation_validation_failed', $request->user(), context: [
                'action' => 'activate',
                'reason_code' => 'confirmation_or_input_invalid',
            ]);

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            $maintenance->activate(
                $request->user(),
                trim($validated['message']),
                isset($validated['planned_end_at'])
                    ? CarbonImmutable::parse($validated['planned_end_at'])
                    : null,
            );
        } catch (MaintenanceTransitionException) {
            return back()->withErrors([
                'maintenance' => 'Maintenance mode could not be activated. Refresh the console and try again.',
            ]);
        }

        return redirect()
            ->route('admin.maintenance.index')
            ->with('status', 'Maintenance mode activated.');
    }

    public function deactivate(
        Request $request,
        MaintenanceLifecycleManager $maintenance,
        AuditLogger $audit,
    ): RedirectResponse {
        $validator = Validator::make($request->all(), [
            'confirm_deactivation' => ['accepted'],
        ]);

        if ($validator->fails()) {
            $audit->record('maintenance.deactivation_validation_failed', $request->user(), context: [
                'action' => 'deactivate',
                'reason_code' => 'confirmation_invalid',
            ]);

            return back()->withErrors($validator);
        }

        try {
            $maintenance->deactivate($request->user());
        } catch (MaintenanceTransitionException) {
            return back()->withErrors([
                'maintenance' => 'Maintenance mode could not be deactivated. Refresh the console and try again.',
            ]);
        }

        return redirect()
            ->route('admin.maintenance.index')
            ->with('status', 'Maintenance mode deactivated.');
    }
}
