<?php

use App\Contracts\ModuleLifecycleManager;
use App\Exceptions\ModuleLifecycleException;
use App\Models\ModuleInstallation;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleInstanceLock;
use App\Services\ModuleRollbackCompatibilityChecker;
use App\Services\PrivilegedMutationAuthorizer;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$application = require dirname(__DIR__, 2).'/bootstrap/app.php';
$application->make(Kernel::class)->bootstrap();

[$script, $mode, $actorId, $ownerId, $version, $stateDirectory] = array_pad(
    $argv,
    6,
    null,
);

if (! is_string($mode)
    || ! in_array(
        $mode,
        [
            'disable',
            'enable',
            'write-hold',
            'rollback-check',
            'lock-hold',
            'lock-probe',
            'dependency-lock-hold',
            'dependency-disable-hold',
            'synchronize',
        ],
        true,
    )
    || filter_var($actorId, FILTER_VALIDATE_INT) === false
    || filter_var($ownerId, FILTER_VALIDATE_INT) === false
    || ! is_string($version)
    || ! is_string($stateDirectory)
    || ! is_dir($stateDirectory)) {
    fwrite(STDERR, 'Invalid module concurrency worker arguments.'.PHP_EOL);
    exit(2);
}

$signal = static function (string $name, array $payload = []) use (
    $stateDirectory,
): void {
    $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
    $target = $stateDirectory.DIRECTORY_SEPARATOR.$name.'.json';
    $temporary = $target.'.'.getmypid().'.tmp';

    if (file_put_contents($temporary, $encoded, LOCK_EX) === false
        || ! rename($temporary, $target)) {
        throw new RuntimeException('Unable to publish worker state.');
    }
};

$waitFor = static function (string $name) use ($stateDirectory): void {
    $path = $stateDirectory.DIRECTORY_SEPARATOR.$name;
    $deadline = microtime(true) + 60;

    while (! is_file($path) && microtime(true) < $deadline) {
        usleep(25_000);
    }

    if (! is_file($path)) {
        throw new RuntimeException('Timed out waiting for worker release.');
    }
};

try {
    if ($mode === 'lock-hold') {
        $application->make(ModuleInstanceLock::class)->run(
            'apes-cic',
            'tickets',
            static function () use ($signal, $waitFor): void {
                $signal('lock-held');
                $waitFor('release-lock');
            },
        );
    } elseif ($mode === 'dependency-lock-hold') {
        $application->make(ModuleInstanceLock::class)->run(
            'shelter-rescue',
            'pet-profiles',
            static function () use ($signal, $waitFor): void {
                $signal('dependency-lock-held');
                $waitFor('release-dependency-lock');
            },
        );
    } elseif ($mode === 'dependency-disable-hold') {
        $application->make(ModuleInstanceLock::class)->run(
            'shelter-rescue',
            'pet-profiles',
            static function () use ($signal, $waitFor): void {
                $signal('dependency-write-locked');
                $waitFor('release-dependency-write');
                $dependency = ModuleInstallation::query()
                    ->where('sub_core_key', 'shelter-rescue')
                    ->where('module_key', 'pet-profiles')
                    ->firstOrFail();
                $dependency->forceFill([
                    'enabled' => false,
                    'disabled_at' => now(),
                    'disabled_by' => null,
                    'lock_version' => $dependency->lock_version + 1,
                ])->save();
                $signal('dependency-write-result', ['status' => 'success']);
            },
        );
    } elseif ($mode === 'synchronize') {
        $signal('synchronize-ready');
        $waitFor('start-synchronize');
        $result = $application->make(ModuleInstallationSynchronizer::class)
            ->synchronize();
        $signal('synchronize-result', [
            'status' => 'success',
            'created' => $result['created'],
            'existing' => $result['existing'],
        ]);
    } elseif ($mode === 'lock-probe') {
        $signal('lock-probe-ready');
        $waitFor('start-lock-probe');
        $application->make(ModuleInstanceLock::class)->run(
            'apes-cic',
            'tickets',
            static function () use ($signal): void {
                $signal('lock-probe-result', ['status' => 'success']);
            },
        );
    } elseif ($mode === 'rollback-check') {
        $signal('rollback-check-ready');
        $waitFor('start-rollback-check');
        $application->make(ModuleRollbackCompatibilityChecker::class)
            ->check($version);
        $signal('rollback-check-result', ['status' => 'success']);
    } elseif ($mode === 'write-hold') {
        $application->make(ModuleInstanceLock::class)->run(
            'apes-cic',
            'tickets',
            static function () use ($ownerId, $signal, $waitFor): void {
                $signal('write-locked');
                $waitFor('release-write');
                SupportTicket::query()->create([
                    'user_id' => (int) $ownerId,
                    'assigned_to' => null,
                    'service_area' => 'general',
                    'subject' => 'Concurrent protected write',
                    'priority' => 'medium',
                    'status' => 'open',
                    'description' => 'A write serialized with module lifecycle.',
                    'closed_at' => null,
                ]);
                $signal('write-result', ['status' => 'success']);
            },
        );
    } else {
        $actor = User::query()->findOrFail((int) $actorId);
        $authorizer = $application->make(PrivilegedMutationAuthorizer::class);
        $application->instance(PrivilegedMutationAuthorizer::class, $authorizer);
        $lifecycle = $application->make(ModuleLifecycleManager::class);
        $signal($mode.'-ready');
        $waitFor('start-'.$mode);

        try {
            $authorizer->runAsLocalQa(
                $actor,
                static fn () => $mode === 'disable'
                    ? $lifecycle->disable(
                        $actor,
                        'apes-cic',
                        'tickets',
                        $version,
                    )
                    : $lifecycle->enable(
                        $actor,
                        'apes-cic',
                        'tickets',
                        $version,
                    ),
            );
            $signal($mode.'-result', ['status' => 'success']);
        } catch (ModuleLifecycleException $exception) {
            $signal($mode.'-result', [
                'status' => 'refused',
                'reason' => $exception->reason,
            ]);
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}

exit(0);
