---
name: larastrict-cache
description: Choose and implement Larastrict Context, memory, or repository caching when adding query reuse, cache keys, expiry, invalidation, or cache lifecycle behavior.
---

# Larastrict caching

Read the target application's applicable `AGENTS.md` and `CONTRIBUTING.md`. Resolve installed-source links from that application when using local skill symlinks.

## Choose the approach

| Need | Approach |
| --- | --- |
| Named typed data shared across application logic | Context |
| Simple local memoization | Inject CacheMe with explicit `Memory` strategy |
| Reuse beyond the current execution | Context with `UseCache`, or explicit repository strategy |
| Every call must see fresh data | Query directly or select `None` |

Find an existing Context or cached operation first. Establish the required lifetime and permitted staleness. Include all result inputs in keys, choose TTL and storage explicitly, and define invalidation after writes. Check worker memory reset behavior before assuming a request or job boundary.

Read only the relevant implementation guide:

- [Typed contexts](references/contexts.md): Context values, keys, persistence opt-in, callbacks, and lifecycle.
- [Direct caching](references/memory-and-repository.md): strategies, TTL, tags, misses, and invalidation.

Follow the source links before calling package APIs. Test through compatible generated helpers using the [testing skill](../larastrict-testing/SKILL.md).
