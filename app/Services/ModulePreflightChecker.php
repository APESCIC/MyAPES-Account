<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use App\Exceptions\ModuleLifecycleException;
use Illuminate\Support\Facades\DB;

class ModulePreflightChecker
{
    private const SUPPORTED_DRIVERS = ['sqlite', 'mysql'];

    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    /** @return array{sub_cores: int, modules: int, shipped: int} */
    public function check(): array
    {
        $driver = DB::connection()->getDriverName();

        if (! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new ModuleLifecycleException('unsupported_database_driver');
        }

        if (count($this->registry->matrix())
            !== count($this->registry->subCores())
                * count($this->registry->modules())) {
            throw new ModuleLifecycleException('registry_matrix');
        }

        return [
            'sub_cores' => count($this->registry->subCores()),
            'modules' => count($this->registry->modules()),
            'shipped' => count($this->registry->shippedInstances()),
        ];
    }
}
