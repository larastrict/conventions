---
name: larastrict
description: Apply Larastrict conventions when creating, changing, or reviewing Laravel domains, models, queries, requests, views, providers, and tests.
---

# Larastrict application work

Read the shared [StrictPHP skill](../strictphp/SKILL.md) first. Then read the target application's applicable `AGENTS.md` and `CONTRIBUTING.md`. Apply the short Laravel rules collected in the target application's `AGENTS.md`. Read the [implementation conventions](references/conventions.md), then only the focused reference needed for the task. LaraStrict rules take precedence when they conflict with the shared StrictPHP principles. With local skill symlinks, resolve root and `vendor/` links from that application.

- Domain structure, capability modules, naming, dependency injection, dates, Action and Query boundaries, result types, or streaming: [architecture](references/architecture.md).
- Constructor injection, framework injection methods, or container-resolution scope: [supported injection points](references/injection-points.md).
- Filament Resources, Pages, Widgets, UI facades, or Livewire lifecycle injection: [Filament and Livewire](references/filament-and-livewire.md).
- DTOs, immutable Entities, mutable StateEntities, or representation conversion: [DTOs and Transformers](references/dto.md).
- Eloquent models, capability traits and interfaces, scopes, casts, or column and relation keys: [models](references/models.md).
- Endpoint slices, Requests, Controllers, HTTP Resources, JetBrains `.http` files, or typed Blade data: [HTTP and views](references/http-and-views.md).
- JSON Resources, Inertia props, or sharing page Entities with Blade: [Resources and Inertia](references/resources-and-inertia.md).
- Schema changes or data backfills: use the [migration skill](../larastrict-migrations/SKILL.md).
- Routes, views, components, typed config, bindings, or provider capabilities: [providers](references/providers.md).
- Contexts, memory, repository caching, keys, and invalidation: use [larastrict-cache](../larastrict-cache/SKILL.md).
- Generated expectations or unit, database, and browser test boundaries: use [larastrict-testing](../larastrict-testing/SKILL.md).

Read the implementation links in the relevant reference before using a package API. Source links point to dependencies in the consumer application; follow the installed version. Follow the consumer's test setup.
