---
name: new-plugin
description: "Scaffold a MyAPES Account module or plugin with php artisan make:module / make:plugin. Use when adding a new organisation area or reusable feature package under modules/ or plugins/."
---

# New module / plugin

## Commands

```bash
php artisan make:module {slug} --name="Display Name" --prefix=/live-prefix
php artisan make:plugin {slug} --name="Display Name" --modules=apes-cic,shelter-rescue --depends=pet-profiles
composer dump-autoload
```

Read and follow [docs/developer-guide.md](../../../docs/developer-guide.md).

## Rules

- Do **not** edit `app/Core` to register a package.
- Keep live URL prefixes and `{module}.{plugin}.*` permission strings.
- `RecruitmentRole` is never Spatie `Role`.
- Run `php artisan test --compact tests/Architecture` after scaffolding.
- Ship release metadata in the same PR (`myapes:changelog-prepare`).
