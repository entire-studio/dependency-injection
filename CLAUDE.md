# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer test        # Run PHPUnit test suite
composer sat         # Run PHPStan static analysis (level 8)
composer style       # Check PSR-12 coding style
composer style:fix   # Auto-fix coding style issues
```

To run a single test:
```bash
vendor/bin/phpunit --testdox -c phpunit.xml --filter testMethodName
```

## Architecture

This is a PSR-11 compatible dependency injection container published as `entire-studio/dependency-injection`.

**Core implementation:** `src/Container.php` implements `Psr\Container\ContainerInterface`. Public API:
- `get($id)` / `has($id)` — PSR-11
- `set($id, Closure|string)` — alias or Closure binding (cached as singleton)
- `factory($id, Closure)` — per-call factory (not cached)
- `value($id, mixed)` — literal value binding
- `unset($id)` / `clear()` — drop bindings; `clear()` preserves self-registration

**Resolution flow:** `get()` walks the alias chain (with cycle detection), then `resolve()` reflects the target class and recursively autowires constructor params. `resolveParameter()` handles every type-hint shape (named class, union, intersection, builtin, self/parent/static, variadic, nullable, defaulted). All errors implementing `Throwable` from user Closures are wrapped as `ContainerException`; inner missing-dep `NotFoundException` is also wrapped so only the top-level requested id can yield NFE.

**Lifecycle:** Singleton-by-default. `factory()` opts out per-id. The container self-registers as `ContainerInterface` / `self::class` / `static::class` in the constructor.

**Exceptions** (`src/Exceptions/`): `ContainerException` implements `ContainerExceptionInterface`, `NotFoundException` implements `NotFoundExceptionInterface` — both extend `Exception`.

**Tests** (`test/ContainerTest.php`) use mock classes in `test/Mocks/` for autowiring, alias, lifecycle, error, and PSR-11 conformance cases.

## Backlog

Tracked in Linear under the **Dependency Injection** project. Use the Linear MCP tools to query current state — don't rely on a snapshot in this file.

## Standards

- PHP 8.2+ required; CI tests against 8.2, 8.3, 8.4, 8.5
- PHPStan level 8 on `src/` and `test/` (excludes `test/Mocks/`)
- PSR-12 coding style enforced via phpcs
- `declare(strict_types=1)` in every file
