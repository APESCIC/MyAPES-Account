<?php

namespace App\Modules;

final readonly class ModuleSummaryGroup
{
    /**
     * @param  list<ModuleSummary>  $summaries
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $summaries,
    ) {}
}
