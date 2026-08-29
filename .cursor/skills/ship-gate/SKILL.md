---
name: ship-gate
description: Gated ship lifecycle from commit/push through merge, Cloudron deploy (workflow_dispatch), changelog hubs, and optional GitHub Release. Use when work is ready to commit or open a PR, when PR checks are green, after merge to main, after deploy, or when the user asks to ship, deploy, release, or update changelogs.
---

# Ship-gate

Use this skill for shipping gates from first commit through post-deploy release. Release **metadata** still uses `release-metadata` before the PR merges.

## Gate reply format

End the turn with:

```markdown
## Next actions
**Recommended:** <one clear choice>

1. ...
2. ...
```

Do not proceed past a gate without an explicit user pick (or a prior explicit order that covers that gate).

## Before PR — commit gate

### When committing is best

Commit when **all** that apply are true:

1. Feature/fix work on the `cursor/...` branch is complete for this slice (or a clear WIP checkpoint the user wants saved).
2. Focused tests for the change have been run (or failure is documented and the user still wants a commit).
3. If the change will merge to `main`: release metadata is prepared on the branch (`myapes:changelog-prepare`, TODOs filled, `changelog-validate` against `origin/main`) — same PR as the work (see `release-metadata`).
4. Diff is reviewed for secrets / unrelated files; commit message will explain *why*.

**Prefer not** to commit: mid-edit with broken tests (unless the user picks WIP), before release metadata when a versioned merge is required, or while still deciding scope.

**Mid-work commits:** only after an explicit user pick at this gate (or a prior “commit WIP” order). Do not invent a cadence of automatic checkpoint commits.

### Present the gate

After the ready-for-review conditions above (or when the user asks to save progress), end the turn with Next actions. **Never commit or push until they pick (or already ordered commit/push).**

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

Prefer `--squash` only if the user asks. Confirm merge landed on `main` before the deploy gate.

## After merge — deploy gate

Confirm main tests for the merge commit, then ask deploy vs skip.

```powershell
gh run list --branch main --workflow "Test MyAPES Core" --limit 3
```

If the user chooses deploy:

```powershell
gh workflow run "Deploy MyAPES Core to Cloudron" --ref main
gh run list --workflow "Deploy MyAPES Core to Cloudron" --limit 1
gh run watch <run-id>
```

Verify live when the run succeeds:

- Deploy run URL / conclusion
- `https://myaccount.myapes.me.uk/healthz` reports the expected `version` and `release` SHA

Deploy is **manual only** (`workflow_dispatch`). There is no auto-deploy on green `main`.

## After deploy — changelog hubs gate

### App Change Log Hub (this repo)

Source of truth: in-PR `resources/data/releases.json` + `VERSION` (via `myapes:changelog-prepare`). The live hub is the same deploy — no separate changelog PR.

Verify:

```powershell
$version = (Get-Content VERSION -Raw).Trim()
# healthz version
Invoke-RestMethod https://myaccount.myapes.me.uk/healthz
# Change Log Hub should list v$version
# Open https://myaccount.myapes.me.uk/change-log and confirm the head title/summary
```

Footer version links also point at this hub.

### Sibling website changelogs (other repos)

These sites keep their **own** `VERSION` / `CHANGELOG.md` / Change Log Hub pages. They are **not** updated by MyAPES Account deploy:

| Site | Typical repo | Typical paths |
| --- | --- | --- |
| APES CIC website | `APESCIC/APES.CIC.Website` | `CHANGELOG.md`, `public/CHANGELOG.md`, `/change-log-hub/` |
| Shelter website | `APESCIC/APES.Shelter.Website` | `CHANGELOG.md`, `public/CHANGELOG.md`, changelog HTML |
| Pet Care website | `APESCIC/APES.Pet.Care.Website` | follow that repo’s AGENTS.md |
| MyAPES marketing site | `APESCIC/MyAPES.Website` | `CHANGELOG.md`, `VERSION`, `public/` |

**Recommended:** verify the app hub only, then continue.

If the user picks sibling updates:

1. Ask which site(s) to touch.
2. Work only in those repos after the user confirms (separate clone/branch/PR there).
3. Do **not** create GitHub issues in other repositories unless the user explicitly orders it.
4. Keep MyAPES Core release notes accurate on `/change-log`; do not duplicate Core-only deploy details onto unrelated marketing sites unless the user wants a cross-link blurb.

## After changelog gate — GitHub Release

If the user chooses create release:

1. Read root `VERSION` (no `v` prefix in the file).
2. Read the head record in `resources/data/releases.json` for title/summary notes.
3. Skip if the tag or release already exists:

```powershell
$version = (Get-Content VERSION -Raw).Trim()
gh release view "v$version"
```

4. Otherwise create:

```powershell
$version = (Get-Content VERSION -Raw).Trim()
gh release create "v$version" --title "<title from releases.json>" --notes "<summary and changes from head record>" --target main
```

Use public-safe notes only (same constraints as changelog prose).

## Completion

Only after merge is verified, and after any chosen deploy / changelog-hub / release steps succeed, give the completion confirmation (PR URL/number, merge SHA, target branch, deploy evidence if deployed, live `/change-log` confirmation if checked, release URL if created, linked issue closed/updated). Offer to archive the agent; do not archive silently.
