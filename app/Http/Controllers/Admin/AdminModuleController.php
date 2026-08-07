<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ModuleLifecycleManager;
use App\Exceptions\ModuleLifecycleException;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleAdministrationCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminModuleController extends Controller
{
    public function index(ModuleAdministrationCatalogue $catalogue): View
    {
        return view('admin.modules.index', $catalogue->matrix());
    }

    public function transition(
        Request $request,
        string $subCoreKey,
        string $moduleKey,
        ModuleLifecycleManager $lifecycle,
        AuditLogger $audit,
    ): RedirectResponse {
        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['install', 'enable', 'disable'])],
            'confirm_action' => ['accepted'],
            'confirm_navigation' => ['accepted'],
            'version' => [
                Rule::requiredIf(
                    static fn (): bool => $request->input('action') !== 'install',
                ),
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        if ($validator->fails()) {
            $audit->record(
                'module.lifecycle_validation_failed',
                $request->user(),
                null,
                [
                    'sub_core_key' => $this->safeKey($subCoreKey),
                    'module_key' => $this->safeKey($moduleKey),
                    'action' => $this->safeAction(
                        (string) $request->input('action'),
                    ),
                    'reason' => 'confirmation_or_input_invalid',
                ],
            );

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            match ($validated['action']) {
                'install' => $lifecycle->install(
                    $request->user(),
                    $subCoreKey,
                    $moduleKey,
                ),
                'enable' => $lifecycle->enable(
                    $request->user(),
                    $subCoreKey,
                    $moduleKey,
                    (int) $validated['version'],
                ),
                'disable' => $lifecycle->disable(
                    $request->user(),
                    $subCoreKey,
                    $moduleKey,
                    (int) $validated['version'],
                ),
            };
        } catch (ModuleLifecycleException) {
            return back()->withErrors([
                'module' => 'The module transition could not be completed. Refresh the page and try again.',
            ]);
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('status', 'Module state updated.');
    }

    private function safeKey(string $value): string
    {
        return preg_match('/^[a-z0-9-]{1,64}$/', $value) === 1
            ? $value
            : 'invalid';
    }

    private function safeAction(string $value): string
    {
        return in_array($value, ['install', 'enable', 'disable'], true)
            ? $value
            : 'invalid';
    }
}
