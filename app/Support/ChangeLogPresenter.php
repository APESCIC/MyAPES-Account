<?php

namespace App\Support;

use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Support\Facades\Gate;

class ChangeLogPresenter
{
    public function __construct(private readonly ReleaseHistoryRepository $releases) {}

    public function viewerCanSeeInternalNotes(?User $user): bool
    {
        return $user instanceof User && Gate::forUser($user)->any([
            AuthorizationProfile::PERMISSION_STAFF_ACCESS,
            AuthorizationProfile::PERMISSION_VOLUNTEER_ACCESS,
            AuthorizationProfile::PERMISSION_STUDENT_ACCESS,
        ]);
    }

    /**
     * @return array{
     *     currentRelease: array<string, mixed>,
     *     releases: list<array<string, mixed>>,
     *     showInternalAudienceFilter: bool,
     *     showInternalNotes: bool,
     * }
     */
    public function viewData(?User $user): array
    {
        $showInternal = $this->viewerCanSeeInternalNotes($user);
        $current = $this->releases->current();

        if ($showInternal) {
            return [
                'currentRelease' => $current,
                'releases' => $this->releases->all(),
                'showInternalAudienceFilter' => true,
                'showInternalNotes' => true,
            ];
        }

        $releases = self::forPublicAudience($this->releases->all());

        return [
            'currentRelease' => self::isPublicFacing($current)
                ? self::publicProjection($current)
                : self::publicCurrentPlaceholder($current),
            'releases' => $releases,
            'showInternalAudienceFilter' => false,
            'showInternalNotes' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $release
     */
    public static function isPublicFacing(array $release): bool
    {
        return in_array('public-facing', $release['audiences'] ?? [], true);
    }

    /**
     * @param  list<array<string, mixed>>  $releases
     * @return list<array<string, mixed>>
     */
    public static function forPublicAudience(array $releases): array
    {
        $visible = [];

        foreach ($releases as $release) {
            if (! self::isPublicFacing($release)) {
                continue;
            }

            $visible[] = self::publicProjection($release);
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array<string, mixed>
     */
    public static function publicProjection(array $release): array
    {
        $audiences = array_values(array_filter(
            is_array($release['audiences'] ?? null) ? $release['audiences'] : [],
            static fn (mixed $audience): bool => $audience === 'public-facing',
        ));

        return [
            'version' => $release['version'] ?? '',
            'date' => $release['date'] ?? '',
            'channel' => $release['channel'] ?? '',
            'type' => $release['type'] ?? '',
            'title' => $release['title'] ?? '',
            'summary' => $release['summary'] ?? '',
            'changes' => is_array($release['changes'] ?? null) ? $release['changes'] : [],
            'affected_areas' => is_array($release['affected_areas'] ?? null) ? $release['affected_areas'] : [],
            'categories' => is_array($release['categories'] ?? null) ? $release['categories'] : [],
            'audiences' => $audiences === [] ? ['public-facing'] : $audiences,
            'version_rationale' => $release['version_rationale'] ?? '',
            'validation' => [],
            'known_limitations' => [],
            'rollback' => '',
            'provenance' => '',
            'references' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array<string, mixed>
     */
    public static function publicCurrentPlaceholder(array $release): array
    {
        return self::publicProjection([
            'version' => $release['version'] ?? '',
            'date' => $release['date'] ?? '',
            'channel' => $release['channel'] ?? '',
            'type' => $release['type'] ?? '',
            'title' => 'Current release',
            'summary' => '',
            'changes' => [],
            'affected_areas' => [],
            'categories' => [],
            'audiences' => ['public-facing'],
            'version_rationale' => '',
        ]);
    }
}
