# Agent instructions for MyAPES Account

## GitHub issues

Search this repository for duplicates before creating an issue. Never open issues in other repositories.

When creating or updating a task, fill the GitHub sidebar completely (templates cannot set fields, milestone, relationships, or Development):

1. Call `list_issue_types` and `list_issue_fields` for `APESCIC/MyAPES-Account`.
2. Create with GitHub MCP `issue_write` (or `gh`) including:
   - **Assignees:** the person doing the work (`bmurphy-apescic` unless another owner is named)
   - **Labels:** `bug`, `enhancement`, or `documentation`
   - **Type:** `Task`, `Bug`, or `Feature`
   - **Fields:** Priority (`Urgent` / `High` / `Medium` / `Low`), Effort (`High` / `Medium` / `Low`); Start date and Target date when known
   - **Milestone:** Public Release v1 (`milestone: 1`) for v1-scoped work
3. **Relationships:** link parent/sub-issues with `sub_issue_write`. Record blocked-by when there is a real dependency.
4. **Development:** branch `cursor/{feature|fix|chore}/<issue-number>-<short-slug>` and open the PR with `Fixes #<n>` or `Closes #<n>` so GitHub links the PR in Development.
5. Comment progress, blockers, and the branch/PR on the issue. Close with `state_reason` `completed`, `not_planned`, or `duplicate`.

Human templates live in `.github/ISSUE_TEMPLATE/`. After a template create, still set fields, milestone, relationships, and Development via the API.

## Releases

Every change merged to `main` prepends exactly one higher semantic version in `resources/data/releases.json`, matching `VERSION` and `resources/data/module-runtime-contract.json`. Do not edit or reorder published records.
