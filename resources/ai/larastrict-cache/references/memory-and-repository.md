# Direct memory and repository caching

Inject `CacheMeServiceContract` for local memoization or explicit repository caching when a named Context adds no useful domain contract. Select `CacheMeStrategy` explicitly for every direct call:

| Strategy | Storage |
| --- | --- |
| `Memory` | Laravel array store |
| `Repository` | Configured default cache store |
| `MemoryAndRepository` | Memory first, then the configured default store |
| `None` | Evaluate the callback without storing the result |

The installed service defaults to `MemoryAndRepository`. Do not rely on that default for local memoization. A configured repository is not necessarily persistent; verify the default store. Use named `seconds` for TTL, and define expiry and invalidation from the permitted staleness of the data.

Include all result inputs in a stable key with a domain prefix. Namespace keys by operation and data format when needed. Use owned constants for shared key prefixes or tags. Invalidate affected keys or tags after successful writes, normally after the database transaction commits. For `MemoryAndRepository`, invalidate both layers. Verify tag support before using tags.

The installed service treats `null` as a cache miss and does not store a callback's `null` result. To memoize a missing result, use an explicit typed value that represents absence. `false` is a value. Use immutable Entity results when the cached value must stay stable.

Use the existing [CacheMeServiceContractAssert](../../../../vendor/wrkflow/larastrict/src/Testing/Cache/Contracts/CacheMeServiceContractAssert.php) or [ContextServiceContractAssert](../../../../vendor/wrkflow/larastrict/src/Testing/Context/Contracts/ContextServiceContractAssert.php) when compatible. Verify keys, chosen strategy, TTL, callback results, and invalidation behavior at the application boundary.

## Read the implementation

Read [CacheMeServiceContract](../../../../vendor/wrkflow/larastrict/src/Cache/Contracts/CacheMeServiceContract.php), [CacheMeStrategy](../../../../vendor/wrkflow/larastrict/src/Cache/Enums/CacheMeStrategy.php), and [CacheMeService](../../../../vendor/wrkflow/larastrict/src/Cache/Services/CacheMeService.php) before writing direct cache calls. Read [CacheServiceProvider](../../../../vendor/wrkflow/larastrict/src/Cache/CacheServiceProvider.php) for registration and memory reset hooks.
