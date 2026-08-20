# Agent release metadata before deploy

**Date:** 2026-08-20  
**Issue:** [#67](https://github.com/APESCIC/MyAPES-Account/issues/67)

## Problem

Release metadata (`VERSION`, `resources/data/releases.json`, `resources/data/module-runtime-contract.json`) must be present before Cloudron deploy can succeed, but agents only had a one-line note in `AGENTS.md`. Feature PRs sometimes merged without a bump, requiring a follow-up release PR (#65 → #66).

## Decisions

| Decision | Choice |
| --- | --- |
| When to bump | Same PR as feature/fix, before merge |
| Mechanism | `myapes:changelog-prepare` (stub-then-fill) + agent narrative |
| CI changes | Extend validator only (manifest sync, reject `TODO:`) |
| Deploy | Unchanged — automatic on green `main` push |

## Flow

```
Feature/fix complete
  → myapes:changelog-prepare
  → agent replaces TODO: fields
  → myapes:changelog-validate --base-ref=origin/main
  → same PR merges to main
  → CI deploys to Cloudron
```

## Components

- **`ReleaseHistoryPreparer`** — semver bump, stub record, sync three metadata files, patch version-pinned tests
- **`myapes:changelog-prepare`** — artisan entrypoint with `--dry-run`
- **`ReleaseHistoryValidator`** — also checks manifest `application_version` and rejects scaffold `TODO:` text
- **Agent docs** — `AGENTS.md`, `.cursor/rules/release-metadata.mdc`, `.cursor/skills/release-metadata/SKILL.md`

## Out of scope

- Separate release PR workflow
- Git tags / GitHub Releases
- Additional deploy workflow changes
