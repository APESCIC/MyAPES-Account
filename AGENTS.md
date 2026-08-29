# Agent instructions for MyAPES Account

## Implement order

`main` holds the merged **v1.0.0 Beta** stack through **v0.31.2** on live (Access/RBAC, password pack, changelog guest filter, stale Super Admin redirects). **`v0.31.x Beta`** is **closed**: epic #91 and live-verify #159 are done. **`v0.32.x Beta`** work may start when asked.

Do not reopen Access/RBAC or password-pack feature PRs for work already on `main` (#97–#99, #142, #121, #147, #148, #122, #133).

## GitHub milestones

Use **minor-line** milestones with a **Beta** suffix until the product exits beta:

- Format: `v{major}.{minor}.x Beta` (for example `v0.32.x Beta`)
- **Patch** releases (`0.32.1`, `0.32.2`, …) stay on the same minor-line milestone
- **Minor** bump (`0.32.x` → `0.33.0`): close the completed `v0.32.x Beta` milestone when its issues are done; assign new work to `v0.33.x Beta`
- Closed historical lines: `v0.1.x Beta` through `v0.30.x Beta` (completed releases)
- **Current completed line:** `v0.31.x Beta` (closed; v1.0.0 stack on live)
- **Active backlog:** `v0.32.x Beta` (Public UX & compliance — former milestone 2), `v0.33.x Beta` (Staff UX — former milestone 3)

Planning lists below still describe feature order; map issues to the semver minor-line milestone above.

### v1.0.0 Beta (`v0.31.x Beta`, closed) — complete on live v0.31.2

1. #91 RBAC epic — closed; code on `main` via #120 / #156 / #157
2. #97 Access admin UX — merged (#120)
3. #98 PHPUnit auth matrix — merged (#156)
4. #99 Public vs Cloudron separation — merged (#157)
5. #158 Local public password pack — closed (children #142, #121, #147, #148, #159)
6. #142 Admin reset of local public password — merged (#160)
7. #121 Public local forgot-password — merged (#161)
8. #147 Signed-in local change-password — merged (#162)
9. #148 Read-only profile email — merged (#163)
10. #159 Live-verify on production — closed completed on live v0.31.2
11. #122 Changelog Internal-only leak — merged (#164)
12. #133 Stale `/superadmin/*` 404s — merged (#165)

### v1.1.0 Beta: Public UX & compliance (`v0.32.x Beta`)

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

### v1.2.0 Beta: Staff UX (`v0.33.x Beta`)

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
   - **Milestone:** the active minor-line Beta milestone — `v0.N.x Beta` (see **GitHub milestones** below). Patch work stays on the current minor-line milestone; open the next `v0.(N+1).x Beta` when `changelog-prepare --type=minor` ships and the previous line is complete.
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
5. Include `VERSION`, `resources/data/releases.json`, `resources/data/module-runtime-contract.json`, and the version-pinned test updates from the prepare command in the **same PR**. Before the first commit or push, present the ship-gate **commit** Next-actions menu (do not auto-commit); then open/update the PR and merge only when CI is green.
6. After merge, ask whether to deploy to Cloudron (`workflow_dispatch` only — no auto-deploy). Pass `app_version` from `VERSION` when dispatching deploy. After a successful deploy, verify the live Change Log Hub (`/change-log`), confirm the GitHub Release display title `{VERSION} Beta` and tag `v{VERSION}` target the deployed SHA (created by the deploy workflow), and ask before updating any sibling website changelogs/hubs. Follow `.cursor/rules/ship-gate.mdc` and `.cursor/skills/ship-gate/SKILL.md`.

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

On Laragon with an Apache virtual host, use **`http://myapes-account.test/`** (or your `-AppUrl` from `bootstrap.ps1 -Laragon`) and run **`composer run dev:laragon`** instead of `composer run dev`. See README “Local development with Laragon (Windows)”.

### Bootstrap when `.env` is missing

If local env is not set up yet, run bootstrap first (installs dependencies, creates `.env` from `.env.local.example`, migrates/seeds, builds frontend):

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\bootstrap.ps1 -Fresh
```

For Laragon, use `-Laragon` (and optional `-AppUrl`) so `.env` is seeded from `.env.laragon.example` instead.

```bash
bash scripts/local/bootstrap.sh --fresh
```

Use `--seed` / `-Seed` for non-destructive seeding. See README “Local environment setup” for details.

### Agent requirement

Reuse an already-running preview; do not start duplicate servers. Open **http://127.0.0.1:8000/** in Cursor's built-in browser (side pane preferred) before UI edits, confirm the page loads, and note the preview URL (and Vite HMR port when relevant) in your reply. Skip preview startup for non-UI tasks.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
