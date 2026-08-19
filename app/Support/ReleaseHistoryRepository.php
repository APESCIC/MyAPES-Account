<?php

namespace App\Support;

use JsonException;
use RuntimeException;
use UnexpectedValueException;

class ReleaseHistoryRepository
{
    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $releases = null;

    private ?string $appVersion = null;

    public function __construct(private readonly ReleaseHistoryValidator $validator) {}

    public function version(): string
    {
        return $this->appVersion ??= $this->readVersionFile(base_path('VERSION'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->releases !== null) {
            return $this->releases;
        }

        $releases = $this->readReleaseFile(resource_path('data/releases.json'));
        $errors = $this->validator->validate($releases, $this->version());

        if ($errors !== []) {
            throw new UnexpectedValueException(implode(PHP_EOL, $errors));
        }

        return $this->releases = $releases;
    }

    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        return $this->all()[0];
    }

    public function readVersionFile(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("Unable to read version file [{$path}].");
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read version file [{$path}].");
        }

        return trim($contents);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readReleaseFile(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Unable to read release file [{$path}].");
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read release file [{$path}].");
        }

        return $this->decodeReleaseJson($contents);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function decodeReleaseJson(string $contents): array
    {
        try {
            $releases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('Release history is not valid JSON.', previous: $exception);
        }

        if (! is_array($releases) || ! array_is_list($releases)) {
            throw new UnexpectedValueException('Release history must be a JSON list.');
        }

        foreach ($releases as $release) {
            if (! is_array($release)) {
                throw new UnexpectedValueException('Every release record must be a JSON object.');
            }
        }

        return $releases;
    }
}
