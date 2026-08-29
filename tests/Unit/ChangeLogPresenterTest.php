<?php

namespace Tests\Unit;

use App\Support\ChangeLogPresenter;
use PHPUnit\Framework\TestCase;

class ChangeLogPresenterTest extends TestCase
{
    public function test_public_audience_omits_internal_only_records_and_internal_fields(): void
    {
        $releases = [
            [
                'version' => '0.3.0',
                'date' => '2026-08-28',
                'channel' => 'stable',
                'type' => 'minor',
                'title' => 'Public profile email',
                'summary' => 'Show a read-only email on Profile.',
                'changes' => ['Show the signed-in account email on Profile.'],
                'affected_areas' => ['Public profile'],
                'categories' => ['added'],
                'audiences' => ['public-facing', 'internal-only'],
                'version_rationale' => 'Minor release adding a public Profile capability.',
                'validation' => ['php artisan test and Cloudron deploy preflight'],
                'known_limitations' => ['LDAP group myapesaccount.staff is unchanged.'],
                'rollback' => 'Redeploy the previous application version.',
                'provenance' => 'Defined by issue #122 and pull request #164.',
                'references' => [
                    [
                        'label' => 'Issue #122',
                        'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/122',
                    ],
                ],
            ],
            [
                'version' => '0.2.0',
                'date' => '2026-08-27',
                'channel' => 'stable',
                'type' => 'patch',
                'title' => 'Remediate development nanoid advisory',
                'summary' => 'Clears GHSA-2v37-7h3g-55p8 in the development lockfile.',
                'changes' => ['Bump nanoid.'],
                'affected_areas' => ['Frontend development lockfile'],
                'categories' => ['fixed', 'security'],
                'audiences' => ['internal-only'],
                'version_rationale' => 'Patch remediating GHSA-2v37-7h3g-55p8.',
                'validation' => ['npm audit'],
                'known_limitations' => ['No production LDAP or deploy is authorized.'],
                'rollback' => 'Revert package-lock.json.',
                'provenance' => 'Defined by issue #45.',
                'references' => [
                    [
                        'label' => 'Issue #45',
                        'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/45',
                    ],
                ],
            ],
            [
                'version' => '0.1.0',
                'date' => '2026-08-26',
                'channel' => 'stable',
                'type' => 'minor',
                'title' => 'Guest authentication redirect correction',
                'summary' => 'Guests return to the public login page.',
                'changes' => ['Redirect browser guests to public login.'],
                'affected_areas' => ['Public login'],
                'categories' => ['fixed'],
                'audiences' => ['public-facing'],
                'version_rationale' => 'Patch correcting guest redirects.',
                'validation' => ['Feature tests'],
                'known_limitations' => ['None.'],
                'rollback' => 'Redeploy the previous application version.',
                'provenance' => 'Defined by issue #7.',
                'references' => [
                    [
                        'label' => 'Issue #7',
                        'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/7',
                    ],
                ],
            ],
        ];

        $public = ChangeLogPresenter::forPublicAudience($releases);

        $this->assertSame(['0.3.0', '0.1.0'], array_column($public, 'version'));
        $this->assertSame(['public-facing'], $public[0]['audiences']);
        $this->assertSame([], $public[0]['validation']);
        $this->assertSame([], $public[0]['known_limitations']);
        $this->assertSame('', $public[0]['rollback']);
        $this->assertSame('', $public[0]['provenance']);
        $this->assertSame([], $public[0]['references']);
        $this->assertSame('Show a read-only email on Profile.', $public[0]['summary']);
        $this->assertFalse(ChangeLogPresenter::isPublicFacing($releases[1]));
    }

    public function test_internal_only_current_release_uses_a_public_placeholder(): void
    {
        $placeholder = ChangeLogPresenter::publicCurrentPlaceholder([
            'version' => '0.4.1',
            'date' => '2026-08-28',
            'channel' => 'stable',
            'type' => 'patch',
            'title' => 'Continuous-integration application-key correction',
            'audiences' => ['internal-only'],
            'rollback' => 'Redeploy using the previous CI secret layout.',
        ]);

        $this->assertSame('0.4.1', $placeholder['version']);
        $this->assertSame('Current release', $placeholder['title']);
        $this->assertSame('', $placeholder['rollback']);
        $this->assertSame([], $placeholder['references']);
        $this->assertSame(['public-facing'], $placeholder['audiences']);
    }

    public function test_with_github_release_reference_appends_release_link_for_staff(): void
    {
        $release = ChangeLogPresenter::withGithubReleaseReference([
            'version' => '0.31.6',
            'references' => [
                [
                    'label' => 'Issue #173',
                    'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/173',
                ],
            ],
        ]);

        $this->assertCount(2, $release['references']);
        $this->assertSame(
            'https://github.com/APESCIC/MyAPES-Account/releases/tag/v0.31.6',
            $release['references'][1]['url'],
        );
        $this->assertSame('GitHub Release v0.31.6', $release['references'][1]['label']);
    }

    public function test_with_github_release_reference_is_idempotent(): void
    {
        $release = [
            'version' => '0.31.6',
            'references' => [
                [
                    'label' => 'GitHub Release v0.31.6',
                    'url' => 'https://github.com/APESCIC/MyAPES-Account/releases/tag/v0.31.6',
                ],
            ],
        ];

        $this->assertSame($release, ChangeLogPresenter::withGithubReleaseReference($release));
    }
}
