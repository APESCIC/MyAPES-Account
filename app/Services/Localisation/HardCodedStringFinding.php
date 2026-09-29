<?php

namespace App\Services\Localisation;

/**
 * @phpstan-type FindingArray array{
 *     area: string,
 *     path: string,
 *     line: int,
 *     kind: string,
 *     snippet: string,
 *     suggested_key: string,
 *     target: string,
 *     plugin: string|null,
 *     allowlisted: bool,
 *     allowlist_reason: string|null
 * }
 */
final readonly class HardCodedStringFinding
{
    public function __construct(
        public string $area,
        public string $path,
        public int $line,
        public string $kind,
        public string $snippet,
        public string $suggestedKey,
        public string $target,
        public ?string $plugin = null,
        public bool $allowlisted = false,
        public ?string $allowlistReason = null,
    ) {}

    /**
     * @return FindingArray
     */
    public function toArray(): array
    {
        return [
            'area' => $this->area,
            'path' => $this->path,
            'line' => $this->line,
            'kind' => $this->kind,
            'snippet' => $this->snippet,
            'suggested_key' => $this->suggestedKey,
            'target' => $this->target,
            'plugin' => $this->plugin,
            'allowlisted' => $this->allowlisted,
            'allowlist_reason' => $this->allowlistReason,
        ];
    }
}
