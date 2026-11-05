# Typed contexts and cache lifetime

Use a Context for named, typed data shared across Actions or Services. Keep Query work in its callback and inject a Query Contract when a typed test boundary is needed. Context reads use `CacheMeServiceContract` with the `Memory` strategy by default. Implement `UseCache` only when repository persistence is required; it selects `MemoryAndRepository` for reads. `UseCacheWithTags` adds cache tags.

Include every input that affects the result in `getCacheKey()`, including tenant or viewer identity when applicable. ContextService prefixes the key with the Context class. Keep key generation stable and explicit. Do not access the HTTP request or request attributes to manage lifetime, and do not add request tokens to keys.

Use `ContextValueContract` results; `AbstractIsContext` supplies `BoolContextValue` for boolean checks. Prefer an immutable typed value for cached results. For a memory-only Context, use `setWithoutCache()`. In the inspected version, `set()` uses CacheMe's default `MemoryAndRepository` strategy even without `UseCache`; `delete()` follows the Context strategy and would clear only memory, leaving the repository entry behind. Use `set()` only for deliberate repository-backed writes after checking the installed implementation. For normal Context reads, retain the callback-based `get()` flow.

Determine the actual memory lifetime from application setup. The installed cache provider clears memory before and after queue jobs. Inspect long-lived HTTP worker setup separately. Larastrict owns lifecycle handling; avoid custom reset tokens. Keep the requested TTL units consistent with the installed call chain: ContextService passes `getCacheTtl()` as CacheMe's `seconds` argument.

Test the callback, Query arguments, typed result, and cache key through generated expectations. Vary tenant/viewer and other result inputs in key tests. Use [testing](../../larastrict-testing/SKILL.md) for helper selection and test boundaries. Application tests should not boot Laravel solely to re-test the package's cache implementation.

## Read the implementation

Read only the implementation for the operation being changed:

- Defining a Context: [AbstractContext](../../../../vendor/wrkflow/larastrict/src/Context/Contexts/AbstractContext.php). Defining its typed value: [ContextValueContract](../../../../vendor/wrkflow/larastrict/src/Context/Contracts/ContextValueContract.php). Boolean Contexts: [AbstractIsContext](../../../../vendor/wrkflow/larastrict/src/Context/Contexts/AbstractIsContext.php).
- Changing reads, writes, or invalidation: the relevant methods in [ContextService](../../../../vendor/wrkflow/larastrict/src/Context/Services/ContextService.php).
- Enabling repository caching: [UseCache](../../../../vendor/wrkflow/larastrict/src/Context/Concerns/UseCache.php). Adding tags: [UseCacheWithTags](../../../../vendor/wrkflow/larastrict/src/Context/Concerns/UseCacheWithTags.php).
- Changing registration: [ContextServiceProvider](../../../../vendor/wrkflow/larastrict/src/Context/ContextServiceProvider.php). Investigating memory reset boundaries: [CacheServiceProvider](../../../../vendor/wrkflow/larastrict/src/Cache/CacheServiceProvider.php).
