#!/usr/bin/env node
/**
 * Map each releases.json version to the git commit where that version landed on main.
 *
 * Usage:
 *   node scripts/local/map-release-commits.mjs [--write] [--base-ref=origin/main]
 *
 * Outputs release-commit-map.json (dry-run prints to stdout unless --write).
 */

import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..');

const HISTORICAL_VERSIONS = new Set([
    '0.1.0',
    '0.2.0',
    '0.2.1',
    '0.3.0',
    '0.4.0',
    '0.4.1',
    '0.4.2',
    '0.5.0',
    '0.9.2',
]);

export function parseArgs(argv) {
    const options = {
        write: false,
        baseRef: 'origin/main',
        output: join(repoRoot, 'release-commit-map.json'),
    };

    for (const arg of argv) {
        if (arg === '--write') {
            options.write = true;
        } else if (arg.startsWith('--base-ref=')) {
            options.baseRef = arg.slice('--base-ref='.length);
        } else if (arg.startsWith('--output=')) {
            options.output = arg.slice('--output='.length);
        } else if (arg === '--help' || arg === '-h') {
            console.log(`Usage: node scripts/local/map-release-commits.mjs [--write] [--base-ref=origin/main] [--output=path]`);
            process.exit(0);
        } else {
            throw new Error(`Unknown argument: ${arg}`);
        }
    }

    return options;
}

export function runGit(args, baseRef = null) {
    const fullArgs = baseRef === null ? args : [...args, baseRef];
    return execFileSync('git', fullArgs, {
        cwd: repoRoot,
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    }).trim();
}

export function runGh(args) {
    return execFileSync('gh', args, {
        cwd: repoRoot,
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    }).trim();
}

export function loadReleases(releasesPath = join(repoRoot, 'resources/data/releases.json')) {
    return JSON.parse(readFileSync(releasesPath, 'utf8'));
}

export function compareVersions(a, b) {
    const pa = a.split('.').map(Number);
    const pb = b.split('.').map(Number);

    for (let i = 0; i < 3; i += 1) {
        const diff = (pa[i] ?? 0) - (pb[i] ?? 0);
        if (diff !== 0) {
            return diff;
        }
    }

    return 0;
}

export function sortVersionsAscending(versions) {
    return [...versions].sort(compareVersions);
}

export function extractPullRequestNumbers(release) {
    const numbers = new Set();
    const patterns = [
        /pull request #(\d+)/gi,
        /pull\/(\d+)/gi,
        /\bPR #(\d+)\b/gi,
    ];

    const sources = [
        ...(release.references ?? []).map((ref) => ref.label ?? ''),
        ...(release.references ?? []).map((ref) => ref.url ?? ''),
        release.provenance ?? '',
    ];

    for (const source of sources) {
        for (const pattern of patterns) {
            pattern.lastIndex = 0;
            let match = pattern.exec(source);
            while (match) {
                numbers.add(Number(match[1]));
                match = pattern.exec(source);
            }
        }
    }

    return [...numbers];
}

export function extractIssueNumbers(release) {
    const numbers = new Set();
    const patterns = [/issue #(\d+)/gi, /issues\/(\d+)/gi];

    const sources = [
        ...(release.references ?? []).map((ref) => ref.label ?? ''),
        ...(release.references ?? []).map((ref) => ref.url ?? ''),
        release.provenance ?? '',
    ];

    for (const source of sources) {
        for (const pattern of patterns) {
            pattern.lastIndex = 0;
            let match = pattern.exec(source);
            while (match) {
                numbers.add(Number(match[1]));
                match = pattern.exec(source);
            }
        }
    }

    return [...numbers];
}

export function mapVersionsFromVersionFile(baseRef) {
    const commits = runGit(['log', '--format=%H', baseRef, '--', 'VERSION']).split('\n').filter(Boolean);
    const mapping = new Map();

    for (const sha of commits) {
        const version = runGit(['show', `${sha}:VERSION`]);
        if (!version || mapping.has(version)) {
            continue;
        }

        mapping.set(version, {
            version,
            commit_sha: sha,
            source: 'version-file',
        });
    }

    return mapping;
}

export function resolvePullRequestMergeCommit(prNumber) {
    try {
        const json = runGh([
            'pr',
            'view',
            String(prNumber),
            '--json',
            'mergeCommit,state,mergedAt',
        ]);
        const data = JSON.parse(json);
        if (data.state !== 'MERGED' || !data.mergeCommit?.oid) {
            return null;
        }

        return {
            commit_sha: data.mergeCommit.oid,
            merged_at: data.mergedAt ?? null,
            pr_number: prNumber,
        };
    } catch {
        return null;
    }
}

export function resolveViaVersionInPullRequestTitle(version) {
    try {
        const json = runGh([
            'pr',
            'list',
            '--state',
            'merged',
            '--search',
            `v${version}`,
            '--json',
            'number,title,mergeCommit',
            '--limit',
            '10',
        ]);
        const pulls = JSON.parse(json);
        const exact = pulls.find((pr) => pr.title.includes(`v${version}`) && pr.mergeCommit?.oid);
        if (exact) {
            return {
                commit_sha: exact.mergeCommit.oid,
                pr_number: exact.number,
            };
        }
    } catch {
        return null;
    }

    return null;
}

export function resolveViaPullRequest(release) {
    for (const prNumber of extractPullRequestNumbers(release)) {
        const resolved = resolvePullRequestMergeCommit(prNumber);
        if (resolved) {
            return {
                version: release.version,
                commit_sha: resolved.commit_sha,
                source: 'pr-merge',
                pr_number: prNumber,
            };
        }
    }

    const viaTitleSearch = resolveViaVersionInPullRequestTitle(release.version);
    if (viaTitleSearch) {
        return {
            version: release.version,
            commit_sha: viaTitleSearch.commit_sha,
            source: 'pr-title-search',
            pr_number: viaTitleSearch.pr_number,
        };
    }

    return null;
}

export function resolveViaGitLogTitle(release, baseRef) {
    const needle = release.title.replace(/"/g, '').slice(0, 48);
    if (!needle) {
        return null;
    }

    try {
        const lines = runGit([
            'log',
            baseRef,
            `--since=${release.date}T00:00:00Z`,
            `--until=${release.date}T23:59:59Z`,
            '--format=%H %s',
            '-n',
            '30',
        ]).split('\n').filter(Boolean);

        for (const line of lines) {
            const [sha, ...subjectParts] = line.split(' ');
            const subject = subjectParts.join(' ');
            if (subject.toLowerCase().includes(needle.toLowerCase())) {
                return {
                    version: release.version,
                    commit_sha: sha,
                    source: 'git-log-title',
                    subject,
                };
            }
        }
    } catch {
        return null;
    }

    return null;
}

export function buildReleaseCommitMap(releases, baseRef = 'origin/main') {
    const byVersion = new Map(mapVersionsFromVersionFile(baseRef));
    const errors = [];
    const warnings = [];

    const releaseByVersion = new Map(releases.map((release) => [release.version, release]));

    for (const release of releases) {
        if (byVersion.has(release.version)) {
            continue;
        }

        const viaPr = resolveViaPullRequest(release);
        if (viaPr) {
            byVersion.set(release.version, viaPr);
            continue;
        }

        const viaTitle = resolveViaGitLogTitle(release, baseRef);
        if (viaTitle) {
            byVersion.set(release.version, viaTitle);
            warnings.push(`Version ${release.version} mapped via git-log title fallback.`);
            continue;
        }

        if (HISTORICAL_VERSIONS.has(release.version)) {
            errors.push(`Historical version ${release.version} is missing a PR merge mapping.`);
        } else {
            errors.push(`Version ${release.version} could not be mapped to a commit.`);
        }
    }

    const mapped = sortVersionsAscending([...releaseByVersion.keys()])
        .map((version) => byVersion.get(version))
        .filter(Boolean);

    const missing = releases
        .map((release) => release.version)
        .filter((version) => !byVersion.has(version));

    const duplicateCommits = new Map();
    for (const entry of mapped) {
        const list = duplicateCommits.get(entry.commit_sha) ?? [];
        list.push(entry.version);
        duplicateCommits.set(entry.commit_sha, list);
    }

    for (const [sha, versions] of duplicateCommits.entries()) {
        if (versions.length > 1) {
            warnings.push(`Commit ${sha.slice(0, 12)} shared by versions: ${versions.join(', ')}`);
        }
    }

    return {
        generated_at: new Date().toISOString(),
        base_ref: baseRef,
        total_releases: releases.length,
        mapped_count: mapped.length,
        missing_versions: missing,
        warnings,
        errors,
        mappings: mapped,
    };
}

function main() {
    const options = parseArgs(process.argv.slice(2));
    const releases = loadReleases();
    const report = buildReleaseCommitMap(releases, options.baseRef);
    const payload = JSON.stringify(report, null, 2);

    if (options.write) {
        writeFileSync(options.output, `${payload}\n`, 'utf8');
        console.log(`Wrote ${options.output}`);
    } else {
        console.log(payload);
    }

    if (report.errors.length > 0) {
        process.exitCode = 1;
    }
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
    main();
}
