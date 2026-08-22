<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\ModuleLifecycleManager;
use App\Exceptions\ModuleLifecycleException;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\ModuleAdministrationCatalogue;
use App\Services\ModuleSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminModuleController extends Controller
{
    public function index(ModuleAdministrationCatalogue $catalogue): View
    {
        return view('admin.modules.index', $catalogue->matrix());
    }

    public function editSettings(
        string $subCoreKey,
        string $moduleKey,
        ModuleSettingsService $settings,
    ): View {
        abort_unless(
            $subCoreKey === 'apes-cic' && $settings->supportsSettings($moduleKey),
            404,
        );

        $record = $settings->record($subCoreKey, $moduleKey);
        abort_unless($record !== null, 404);

        return view('admin.modules.settings', [
            'subCoreKey' => $subCoreKey,
            'moduleKey' => $moduleKey,
            'record' => $record,
            'settings' => $record->settings,
            'groupKey' => $moduleKey === 'tickets' ? 'service_areas' : 'categories',
            'groupLabel' => $moduleKey === 'tickets' ? 'Service areas' : 'Case categories',
            'canManage' => request()->user()?->can('admin.modules.manage') ?? false,
        ]);
    }

    public function updateSettings(
        Request $request,
        string $subCoreKey,
        string $moduleKey,
        ModuleSettingsService $settings,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_unless(
            $subCoreKey === 'apes-cic' && $settings->supportsSettings($moduleKey),
            404,
        );

        if ($request->boolean('reset_defaults')) {
            $validated = $request->validate([
                'version' => ['required', 'integer', 'min:1'],
                'confirm_reset' => ['accepted'],
            ]);

            try {
                $record = $settings->resetToDefaults(
                    $subCoreKey,
                    $moduleKey,
                    (int) $validated['version'],
                    $request->user(),
                );
            } catch (ValidationException $exception) {
                return back()->withErrors($exception->errors())->withInput();
            }

            $audit->record('module.settings_reset', $request->user(), null, [
                'sub_core_key' => $subCoreKey,
                'module_key' => $moduleKey,
                'lock_version' => $record->lock_version,
            ]);

            return redirect()
                ->route('admin.modules.settings.edit', [$subCoreKey, $moduleKey])
                ->with('status', 'Module settings reset to defaults.');
        }

        $groupKey = $moduleKey === 'tickets' ? 'service_areas' : 'categories';
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'websites' => ['required', 'array', 'min:1'],
            'websites.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'websites.*.label' => ['required', 'string', 'max:255'],
            'websites.*.url' => ['nullable', 'string', 'max:2048'],
            $groupKey => ['required', 'array', 'min:1'],
            "{$groupKey}.*.key" => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            "{$groupKey}.*.label" => ['required', 'string', 'max:255'],
            "{$groupKey}.*.subcategories" => ['required', 'array', 'min:1'],
            "{$groupKey}.*.subcategories.*.key" => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            "{$groupKey}.*.subcategories.*.label" => ['required', 'string', 'max:255'],
            "{$groupKey}.*.subcategories.*.requires_website" => ['nullable', 'boolean'],
            "{$groupKey}.*.subcategories.*.allows_attachments" => ['nullable', 'boolean'],
        ]);

        $payload = [
            'websites' => collect($validated['websites'])
                ->map(static fn (array $website): array => [
                    'key' => $website['key'],
                    'label' => $website['label'],
                    'url' => $website['url'] ?? null,
                ])
                ->values()
                ->all(),
            $groupKey => collect($validated[$groupKey])
                ->map(static function (array $group): array {
                    return [
                        'key' => $group['key'],
                        'label' => $group['label'],
                        'subcategories' => collect($group['subcategories'])
                            ->map(static fn (array $sub): array => [
                                'key' => $sub['key'],
                                'label' => $sub['label'],
                                'requires_website' => (bool) ($sub['requires_website'] ?? false),
                                'allows_attachments' => (bool) ($sub['allows_attachments'] ?? false),
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];

        try {
            $record = $settings->save(
                $subCoreKey,
                $moduleKey,
                $payload,
                (int) $validated['version'],
                $request->user(),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $audit->record('module.settings_updated', $request->user(), null, [
            'sub_core_key' => $subCoreKey,
            'module_key' => $moduleKey,
            'lock_version' => $record->lock_version,
        ]);

        return redirect()
            ->route('admin.modules.settings.edit', [$subCoreKey, $moduleKey])
            ->with('status', 'Module settings saved.');
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
