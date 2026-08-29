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
| Deploy | Manual `workflow_dispatch` after merge (ship-gate); optional GitHub Release after successful deploy |

## Flow

```
Feature/fix complete
  → myapes:changelog-prepare
  → agent replaces TODO: fields
  → myapes:changelog-validate --base-ref=origin/main
  → same PR merges to main
  → (ask) deploy via workflow_dispatch
  → verify live Change Log Hub (/change-log from releases.json)
  → (ask) sibling website changelogs only if operator wants them
  → (ask) create GitHub Release from VERSION + releases.json head
```

## Components

- **`ReleaseHistoryPreparer`** — semver bump, stub record, sync three metadata files, patch version-pinned tests
- **`myapes:changelog-prepare`** — artisan entrypoint with `--dry-run`
- **`ReleaseHistoryValidator`** — also checks manifest `application_version` and rejects scaffold `TODO:` text
- **Agent docs** — `AGENTS.md`, `.cursor/rules/release-metadata.mdc`, `.cursor/skills/release-metadata/SKILL.md`, ship-gate rule/skill

## Out of scope (original)

- Separate release PR workflow for metadata

## Later change (#167)

- Auto-deploy on green `main` removed; deploy is `workflow_dispatch` only
- GitHub Releases are an optional post-deploy agent gate (not created by CI)
- Agents verify the MyAPES Core Change Log Hub after deploy and ask before updating sibling website changelog hubs
