# Agent instructions for MyAPES Account

## Implement order

`main` holds the merged v1.0.0 Beta code stack through **v0.31.2** (Access/RBAC, password pack, changelog guest filter, stale Super Admin redirects). Do **not** start v1.1.0 until **#159 is closed on live**.

Do not reopen Access/RBAC or password-pack feature PRs for work already on `main` (#97–#99, #142, #121, #147, #148, #122, #133).

### v1.0.0 Beta (`milestone: 1`)

1. #91 RBAC epic — code on `main` via #120 / #156 / #157
2. #97 Access admin UX — merged (#120)
3. #98 PHPUnit auth matrix — merged (#156)
4. #99 Public vs Cloudron separation — merged (#157)
5. #158 Local public password pack (epic; children are #142, #121, #147, #148, #159 — not under closed #129)
6. #142 Admin reset of local public password — merged (#160)
7. #121 Public local forgot-password — merged (#161)
8. #147 Signed-in local change-password — merged (#162)
9. #148 Read-only profile email — merged (#163)
10. #159 Live-verify on production (**remaining gate** — pack is not done until this is closed on live)
11. #122 Changelog Internal-only leak — merged (#164)
12. #133 Stale `/superadmin/*` 404s — merged (#165)

### v1.1.0 Beta: Public UX & compliance (`milestone: 2`)

Only after #159 is closed on live:

1. #128 Branded 404 and 403
2. #130 Signed-in home still shows login doors
3. #131 Spike dock covers fields
4. #132 UK dd/mm/yyyy dates
5. #149 CIC subcategory default
6. #152 Hide Urgent from public tickets
7. #150 Public ticket Activity
8. #151 Public pet owner/date/ID
9. #153 Public save flash
10. #123 Privacy/cookies/help/terms
11. #124 Register consent timestamp
12. #125 Core vs Account naming
13. #126 SECURITY.md
14. #127 Compress Spike/logo PNGs

### v1.2.0 Beta: Staff UX (`milestone: 3`)

1. #136 Hub Create/View URLs (incl. `/shelter/pet-profiles`)
2. #143 Empty pet dropdown on create forms
3. #134 Staff empty states
4. #135 List above create form
5. #141 Duplicate Admin/Super Admin KPI cards
6. #137 Raw permission dump
7. #138 Staff Login / Cloudron chase (never the public reset on #121)
8. #139 Blocked vs INCOMPATIBLE copy
9. #144 Suspend user confirm
10. #140 Group member counts clickable
11. #145 Groups last-sync as-of

## GitHub issues

Search this repository for duplicates before creating an issue. Never open issues in other repositories.

When creating or updating a task, fill the GitHub sidebar completely (templates cannot set fields, milestone, relationships, or Development):

1. Call `list_issue_types` and `list_issue_fields` for `APESCIC/MyAPES-Account`.
2. Create with GitHub MCP `issue_write` (or `gh`) including:
   - **Assignees:** the person doing the work (`bmurphy-apescic` unless another owner is named)
   - **Labels:** `area:` only — `area:auth`, `area:admin`, `area:public`, `area:cloudron`, `area:tests`. Do not use `type:` or `priority:` labels. GitHub Issue Type and Priority/Effort fields cover type and priority.
   - **Type:** `Task`, `Bug`, or `Feature`
   - **Fields:** Priority (`Urgent` / `High` / `Medium` / `Low`), Effort (`High` / `Medium` / `Low`); Start date and Target date when known
   - **Milestone:** the matching Beta milestone — v1.0.0 Beta (`milestone: 1`), v1.1.0 Beta: Public UX & compliance (`milestone: 2`), or v1.2.0 Beta: Staff UX (`milestone: 3`). Do not dump work onto a catch-all “Public Release v1”.
3. **Relationships:** link parent/sub-issues with `sub_issue_write`. Record blocked-by when there is a real dependency.
4. **Development:** branch `cursor/{feature|fix|chore}/<issue-number>-<short-slug>` and open the PR with `Fixes #<n>` or `Closes #<n>` so GitHub links the PR in Development.
5. Comment progress, blockers, and the branch/PR on the issue. Close with `state_reason` `completed`, `not_planned`, or `duplicate`.

Human templates live in `.github/ISSUE_TEMPLATE/`. After a template create, still set fields, milestone, relationships, and Development via the API.

## Releases

Every change merged to `main` must include release metadata in the **same pull request** as the feature or fix work. Do not open a separate release-only PR after merge.

### End-of-work checklist

1. Complete the feature/fix and its tests on the working branch.
2. Scaffold the next release:
   ```powershell
   php artisan myapes:changelog-prepare --type=patch --title="Short public title" --issue=<n> [--pr=<n>]
   ```
   Use `--type=minor` for backward-compatible capabilities and `--type=patch` for compatible fixes. While the app is pre-1.0, document breaking changes and bump **minor**.
3. Replace every `TODO:` field in the new head record of `resources/data/releases.json` with reviewed public prose. Keep notes free of credentials, personal data, private operational identifiers, exploitable security detail, and unnecessary infrastructure detail.
4. Validate against remote `main`:
   ```powershell
   git fetch origin
   php artisan myapes:changelog-validate --base-ref=origin/main
   ```
5. Include `VERSION`, `resources/data/releases.json`, `resources/data/module-runtime-contract.json`, and the version-pinned test updates from the prepare command in the **same PR**. Merge only when CI is green.
6. Cloudron deploy runs automatically after a successful merge to `main`; do not deploy manually for normal releases.

The prepare command syncs `VERSION`, prepends one release record, updates `module-runtime-contract.json` → `application_version`, and patches version-pinned tests. Do not edit or reorder published release records. See `.cursor/skills/release-metadata/SKILL.md` for full guidance.

### Pre-merge validation

Before pushing a release branch, run:

```powershell
composer pre-merge
```

Or:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\pre-merge.ps1
```

This validates release history against `origin/main`, version-pinned tests, frontend tests, and a production build.

## Local preview

Before planning or editing UI-facing files, ensure a local preview is running and open the app in Cursor's built-in browser.

### Start the stack

Prefer the cross-platform Composer script from the repository root:

```powershell
composer run dev
```

On Windows you may also use:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\dev.ps1
```

On macOS / Linux:

```bash
bash scripts/local/dev.sh
```

These start Laravel, the queue listener, application logs, and Vite together. Default app URL: **http://127.0.0.1:8000/** (override with `APP_PORT`). Vite HMR typically listens on **http://127.0.0.1:5173/**.

### Bootstrap when `.env` is missing

If local env is not set up yet, run bootstrap first (installs dependencies, creates `.env` from `.env.local.example`, migrates/seeds, builds frontend):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\bootstrap.ps1 -Fresh
```

```bash
bash scripts/local/bootstrap.sh --fresh
```

Use `--seed` / `-Seed` for non-destructive seeding. See README “Local environment setup” for details.

### Agent requirement

Reuse an already-running preview; do not start duplicate servers. Open **http://127.0.0.1:8000/** in Cursor's built-in browser (side pane preferred) before UI edits, confirm the page loads, and note the preview URL (and Vite HMR port when relevant) in your reply. Skip preview startup for non-UI tasks.
