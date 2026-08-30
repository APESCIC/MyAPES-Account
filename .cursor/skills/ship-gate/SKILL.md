---
name: ship-gate
description: Gated ship lifecycle from local dev verify through commit/PR, merge, changelog hubs, Cloudron deploy (workflow_dispatch) last, and automated GitHub Release verification after deploy.
---

# Ship-gate

Use this skill for shipping gates from local verification through post-deploy completion. Release **metadata** still uses `release-metadata` before the PR merges.

## Gate reply format

End the turn with:

```markdown
## Next actions
**Recommended:** <one clear choice>

1. ...
2. ...
```

Do not proceed past a gate without an explicit user pick (or a prior explicit order that covers that gate).

## Before PR — local dev verify gate

Present after focused tests pass and release metadata validates, but **before** the commit gate.

### When local verify is best

Run local dev verify when **all** that apply are true:

1. Feature/fix work on the `cursor/...` branch is complete for this slice.
2. Release metadata is prepared when a versioned merge is required (`myapes:changelog-prepare`, TODOs filled, `myapes:changelog-validate --base-ref=origin/main`).
3. Focused tests for the change have been run (or failure is documented).

### Recommended steps

Prefer the wrapper script:

```powershell
composer pre-pr-verify
```

Or run manually:

| Step | Command / action | Purpose |
| --- | --- | --- |
| Sync deps | `composer install`, `npm install` | Match PR branch |
| Contract gate | `composer pre-merge` | Release history, version-pinned tests, frontend tests, production build |
| Upgrade path | `bootstrap.ps1 -Seed` or `bootstrap.sh --seed` | Non-destructive migrate + seed on existing local `.env` |
| Install test | `bootstrap.ps1 -Fresh` or `bootstrap.sh --fresh` | Destructive fresh-install simulation when bootstrap, migrations, or install docs changed |
| Run stack | `composer run dev` or `composer run dev:laragon` | Reuse running preview; do not start duplicate servers |
| Smoke checks | `GET /healthz`, spot-check changed routes, open `/change-log` locally | Verify the slice before PR |

Default URLs: `http://127.0.0.1:8000/` (artisan serve) or `http://myapes-account.test/` (Laragon with `-Laragon` bootstrap).

Smoke example:

```powershell
$version = (Get-Content VERSION -Raw).Trim()
Invoke-RestMethod http://127.0.0.1:8000/healthz
# Confirm version matches $version; open http://127.0.0.1:8000/change-log
```

### Present the gate

```markdown
## Next actions
**Recommended:** Local verify passed — continue to commit/PR

1. Local verify passed — continue to commit/PR
2. Re-run local verify (install test with -Fresh)
3. Fix issues and retry
4. Skip local verify (document why)
5. Stop / leave as-is
```

On pick 1, continue to the commit gate. On pick 2, rerun with `-Fresh` bootstrap before returning to this gate.

## Before PR — commit gate

### When committing is best

Commit when **all** that apply are true:

1. Feature/fix work on the `cursor/...` branch is complete for this slice (or a clear WIP checkpoint the user wants saved).
2. Focused tests for the change have been run (or failure is documented and the user still wants a commit).
3. If the change will merge to `main`: release metadata is prepared on the branch (`myapes:changelog-prepare`, TODOs filled, `changelog-validate` against `origin/main`) — same PR as the work (see `release-metadata`).
4. Local dev verify passed (or skip documented at the local verify gate).
5. Diff is reviewed for secrets / unrelated files; commit message will explain *why*.

**Prefer not** to commit: mid-edit with broken tests (unless the user picks WIP), before release metadata when a versioned merge is required, before local verify (unless skipped), or while still deciding scope.

**Mid-work commits:** only after an explicit user pick at this gate (or a prior “commit WIP” order). Do not invent a cadence of automatic checkpoint commits.

### Present the gate

After local verify passes (or is skipped) and ready-for-review conditions hold, end the turn with Next actions. **Never commit or push until they pick (or already ordered commit/push).**

**Recommended:** Commit and open/update PR when the slice is review-ready (release metadata included if needed). Prefer Commit only when a PR already exists and only the commit is missing.

```markdown
## Next actions
**Recommended:** Commit and open/update PR

1. Commit and open/update PR
2. Commit only (no push / no PR yet)
3. Commit WIP checkpoint (incomplete; message notes WIP)
4. Keep working uncommitted
5. Stop / leave as-is
```

### On pick 1–3

Follow the repo commit protocol:

- Parallel `git status`, staged/unstaged `git diff`, and recent `git log` for message style
- Selective add (no secrets); concise message focused on *why*
- HEREDOC commit message; no `--no-verify`; no amend unless amend rules are met

**Option 1:** then `git push -u` if needed and `gh pr create` / update with `Fixes #<n>` or `Closes #<n>`.

**Option 2:** commit locally only; do not push or open a PR until a later gate pick or explicit order.

**Option 3:** commit with a message that clearly notes WIP / incomplete; same push/PR restraint as option 2 unless the user also ordered push.

After a PR exists, continue into the “After PR CI is green” gate (do not merge without that gate).

## After PR CI is green

```powershell
gh pr view --json number,url,headRefName,mergeable
gh pr checks --json name,bucket,state,workflow,link
```

Watch pending checks:

```powershell
gh pr checks --watch --fail-fast
```

**Recommended** when checks are green and release metadata is in the PR: merge.

```powershell
gh pr merge --merge --delete-branch
```

Prefer `--squash` only if the user asks. Confirm merge landed on `main` before the changelog gate.

## After merge — changelog hubs gate

### App Change Log Hub (this repo)

Source of truth: in-PR `resources/data/releases.json` + `VERSION` (via `myapes:changelog-prepare`). The live hub is published by Cloudron deploy — no separate changelog PR.

On `main`, confirm the head record matches `VERSION`:

```powershell
$version = (Get-Content VERSION -Raw).Trim()
php artisan myapes:changelog-validate
# Optional local preview: open http://127.0.0.1:8000/change-log
```

Defer **live** verification until after Cloudron deploy succeeds.

Footer version links also point at this hub.

### Sibling website changelogs (other repos)

These sites keep their **own** `VERSION` / `CHANGELOG.md` / Change Log Hub pages. They are **not** updated by MyAPES Account deploy:

| Site | Typical repo | Typical paths |
| --- | --- | --- |
| APES CIC website | `APESCIC/APES.CIC.Website` | `CHANGELOG.md`, `public/CHANGELOG.md`, `/change-log-hub/` |
| Shelter website | `APESCIC/APES.Shelter.Website` | `CHANGELOG.md`, `public/CHANGELOG.md`, changelog HTML |
| Pet Care website | `APESCIC/APES.Pet.Care.Website` | follow that repo’s AGENTS.md |
| MyAPES marketing site | `APESCIC/MyAPES.Website` | `CHANGELOG.md`, `VERSION`, `public/` |

**Recommended:** confirm metadata on `main`, then continue to Cloudron deploy.

If the user picks sibling updates:

1. Ask which site(s) to touch.
2. Work only in those repos after the user confirms (separate clone/branch/PR there).
3. Do **not** create GitHub issues in other repositories unless the user explicitly orders it.
4. Keep MyAPES Core release notes accurate on `/change-log`; do not duplicate Core-only deploy details onto unrelated marketing sites unless the user wants a cross-link blurb.

## After changelog gate — Cloudron deploy

Confirm main tests for the merge commit, then ask deploy vs skip. Deploy only after pre-merge local verify and main CI are green.

```powershell
gh run list --branch main --workflow "Test MyAPES Core" --limit 3
```

If the user chooses deploy:

```powershell
$version = (git show origin/main:VERSION).Trim()
gh workflow run "Deploy MyAPES Core to Cloudron" --ref main -f app_version="$version"
gh run list --workflow "Deploy MyAPES Core to Cloudron" --limit 1
gh run watch <run-id>
```

Verify live when the run succeeds:

- Deploy run URL / conclusion (run title should read `Deploy v<version>`)
- GitHub **Deployments** entry shows `v<version> (<sha>)` for `cloudron-deploy`
- `https://myaccount.myapes.me.uk/healthz` reports the expected `version` and `release` SHA

Deploy is **manual only** (`workflow_dispatch`). There is no auto-deploy on green `main`.

## After Cloudron deploy — live hub and GitHub Release verification

Successful Cloudron deploy runs **Publish GitHub Release** automatically. The release **name** is `{VERSION} Beta` (for example `0.31.12 Beta`); the tag stays `v{VERSION}`. The feature title stays in the release body (from `scripts/deploy/github-release-notes.sh` / `releases.json`).

**Recommended:** verify the live Change Log Hub and GitHub Release match the deployed version and SHA.

```powershell
$version = (Get-Content VERSION -Raw).Trim()
$health = Invoke-RestMethod https://myaccount.myapes.me.uk/healthz
gh release view "v$version" --json tagName,name,targetCommitish
# Open https://myaccount.myapes.me.uk/change-log and confirm the head title/summary
```

Confirm:

- Live `/change-log` matches the head `releases.json` record
- `name` is `$version Beta`
- `tagName` is `v$version`
- `targetCommitish` equals `$health.release` (deployed SHA)
- Release body matches the public-safe head record in `releases.json`

If a historical tag is missing, use the backfill helper (keeps notes aligned with `releases.json`):

```powershell
node scripts/local/map-release-commits.mjs --write
node scripts/local/backfill-github-releases.mjs --from=$version --resume
```

To retroactively fix display titles on existing releases: `bash scripts/github/rename-release-titles.sh`

Do not create a duplicate GitHub Release manually unless the deploy workflow failed before the publish job and you are recovering. Staff viewers on `/change-log` also see a **GitHub Release v{version}** link in the Source section.

## Completion

Only after merge is verified, and after any chosen changelog-hub / Cloudron deploy / live-verification steps succeed, give the completion confirmation (PR URL/number, merge SHA, target branch, local verify evidence in the PR test plan, deploy evidence if deployed, live `/change-log` confirmation if checked, GitHub Release URL if verified, linked issue closed/updated). Offer to archive the agent; do not archive silently.

If the user skipped Cloudron deploy, completion does not require live deploy evidence; note the skip explicitly.
