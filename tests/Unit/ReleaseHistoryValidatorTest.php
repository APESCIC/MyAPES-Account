<?php

namespace Tests\Unit;

use App\Support\ReleaseHistoryValidator;
use PHPUnit\Framework\TestCase;

class ReleaseHistoryValidatorTest extends TestCase
{
    public function test_valid_release_history_is_accepted(): void
    {
        $this->assertSame([], $this->validator()->validate($this->validHistory(), '0.6.0'));
    }

    public function test_invalid_versions_duplicates_and_order_are_rejected(): void
    {
        $invalidVersion = $this->validHistory();
        $invalidVersion[0]['version'] = 'v0.6';

        $duplicateVersion = $this->validHistory();
        $duplicateVersion[1]['version'] = '0.6.0';

        $wrongOrder = $this->validHistory();
        [$wrongOrder[0], $wrongOrder[1]] = [$wrongOrder[1], $wrongOrder[0]];

        $this->assertContains('Release 1 has an invalid semantic version.', $this->validator()->validate($invalidVersion, '0.6.0'));
        $this->assertContains('Release versions must be unique.', $this->validator()->validate($duplicateVersion, '0.6.0'));
        $this->assertContains('Release versions must be ordered newest first.', $this->validator()->validate($wrongOrder, '0.6.0'));
    }

    public function test_invalid_dates_and_date_order_are_rejected(): void
    {
        $invalidDate = $this->validHistory();
        $invalidDate[0]['date'] = '27/07/2026';

        $wrongOrder = $this->validHistory();
        $wrongOrder[1]['date'] = '2026-07-28';

        $this->assertContains('Release 1 has an invalid ISO release date.', $this->validator()->validate($invalidDate, '0.6.0'));
        $this->assertContains('Release dates must be non-increasing.', $this->validator()->validate($wrongOrder, '0.6.0'));
    }

    public function test_missing_fields_and_unsupported_classifications_are_rejected(): void
    {
        $missingField = $this->validHistory();
        unset($missingField[0]['summary']);

        $invalidClassifications = $this->validHistory();
        $invalidClassifications[0]['channel'] = 'preview';
        $invalidClassifications[0]['type'] = 'hotfix';
        $invalidClassifications[0]['categories'][] = 'performance';
        $invalidClassifications[0]['audiences'][] = 'private';

        $missingErrors = $this->validator()->validate($missingField, '0.6.0');
        $classificationErrors = $this->validator()->validate($invalidClassifications, '0.6.0');

        $this->assertContains('Release 1 is missing required field [summary].', $missingErrors);
        $this->assertContains('Release 1 has an unsupported channel.', $classificationErrors);
        $this->assertContains('Release 1 has an unsupported version type.', $classificationErrors);
        $this->assertContains('Release 1 has unsupported categories.', $classificationErrors);
        $this->assertContains('Release 1 has unsupported audiences.', $classificationErrors);
    }

    public function test_version_file_must_match_the_newest_release(): void
    {
        $this->assertContains(
            'VERSION must match the newest release record.',
            $this->validator()->validate($this->validHistory(), '0.5.0'),
        );
    }

    public function test_manifest_version_must_match_version_file(): void
    {
        $this->assertContains(
            'module-runtime-contract.json application_version must match VERSION.',
            $this->validator()->validate($this->validHistory(), '0.6.0', '0.5.0'),
        );
    }

    public function test_scaffold_todo_placeholders_are_rejected(): void
    {
        $history = $this->validHistory();
        $history[0]['summary'] = 'TODO: Replace with a security-safe public summary.';

        $this->assertContains(
            'Release 1 still contains scaffold TODO: Replace placeholders.',
            $this->validator()->validate($history, '0.6.0'),
        );
    }

    public function test_unexpected_fields_and_credential_shaped_text_are_rejected(): void
    {
        $history = $this->validHistory();
        $history[0]['password'] = 'not-a-public-field';
        $history[0]['summary'] = 'Accidentally published ghp_abcdefghijklmnopqrstuvwxyz123456.';

        $errors = $this->validator()->validate($history, '0.6.0');

        $this->assertContains('Release 1 contains unsupported field [password].', $errors);
        $this->assertContains('Release 1 contains text that resembles a credential.', $errors);
    }

    public function test_append_only_comparison_accepts_one_higher_head_record(): void
    {
        $current = $this->validHistory();
        $base = array_slice($current, 1);

        $this->assertSame(
            [],
            $this->validator()->validateAppendOnly($current, '0.6.0', $base, '0.5.0'),
        );
    }

    public function test_append_only_comparison_rejects_missing_or_multiple_new_records(): void
    {
        $base = array_slice($this->validHistory(), 1);

        $this->assertContains(
            'Release history must prepend exactly one new record.',
            $this->validator()->validateAppendOnly($base, '0.5.0', $base, '0.5.0'),
        );

        $multiple = $this->validHistory();
        array_unshift($multiple, [
            ...$multiple[0],
            'version' => '0.7.0',
            'date' => '2026-07-28',
            'title' => 'Another release',
        ]);

        $this->assertContains(
            'Release history must prepend exactly one new record.',
            $this->validator()->validateAppendOnly($multiple, '0.7.0', $base, '0.5.0'),
        );
    }

    public function test_append_only_comparison_rejects_modified_or_reordered_history(): void
    {
        $current = $this->validHistory();
        $base = array_slice($current, 1);

        $modified = $current;
        $modified[1]['summary'] = 'Rewritten published history.';

        $reordered = $current;
        [$reordered[1], $reordered[2]] = [$reordered[2], $reordered[1]];

        $this->assertContains(
            'Published release records must remain unchanged and in order.',
            $this->validator()->validateAppendOnly($modified, '0.6.0', $base, '0.5.0'),
        );
        $this->assertContains(
            'Published release records must remain unchanged and in order.',
            $this->validator()->validateAppendOnly($reordered, '0.6.0', $base, '0.5.0'),
        );
    }

    public function test_append_only_comparison_requires_a_higher_version(): void
    {
        $current = $this->validHistory();
        $base = array_slice($current, 1);
        $current[0]['version'] = '0.4.9';

        $this->assertContains(
            'The new release version must be higher than the base version.',
            $this->validator()->validateAppendOnly($current, '0.4.9', $base, '0.5.0'),
        );
    }

    private function validator(): ReleaseHistoryValidator
    {
        if (! class_exists(ReleaseHistoryValidator::class)) {
            $this->fail('ReleaseHistoryValidator has not been implemented.');
        }

        return new ReleaseHistoryValidator;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validHistory(): array
    {
        return [
            $this->release('0.6.0', '2026-07-27', 'stable'),
            $this->release('0.5.0', '2026-07-27', 'stable'),
            $this->release('0.4.2', '2026-07-24', 'stable'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function release(string $version, string $date, string $channel): array
    {
        return [
            'version' => $version,
            'date' => $date,
            'channel' => $channel,
            'type' => str_ends_with($version, '.0') ? 'minor' : 'patch',
            'title' => "Release {$version}",
            'summary' => 'A security-safe public summary.',
            'changes' => ['Implemented a reviewed capability.'],
            'affected_areas' => ['MyAPES Account'],
            'categories' => ['added', 'accessibility'],
            'audiences' => ['public-facing'],
            'version_rationale' => 'A backward-compatible capability.',
            'validation' => ['Automated tests passed.'],
            'known_limitations' => ['No production deployment was performed.'],
            'rollback' => 'Restore the previous reviewed release.',
            'provenance' => 'Reconstructed from a merged pull request.',
            'references' => [
                [
                    'label' => 'PR #1',
                    'url' => 'https://github.com/APESCIC/MyAPES-Account/pull/1',
                ],
            ],
        ];
    }
}
