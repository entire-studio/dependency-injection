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

This is a minimal PSR-11 compatible dependency injection container published as `entire-studio/dependency-injection`.

**Single source file:** `src/Container.php` — the entire container implementation lives here. It implements `Psr\Container\ContainerInterface` with three public methods: `get`, `has`, and `set`.

**Resolution flow in `Container::resolve()`:**
1. Check if a class/interface is registered via `set()` — if so, resolve the registered alias or call the callable with the container as argument.
2. Reflect the target class constructor to auto-wire dependencies recursively.
3. Throw `ContainerException` for union types, missing type hints, or non-instantiable classes.
4. Throw `NotFoundException` for non-existent classes.

**Limitations by design:** Built-in (scalar) constructor parameters cannot be auto-wired — register a callable via `set()` to handle those cases (see `examples/callable.php`).

**Exceptions** (`src/Exceptions/`): `ContainerException` implements `ContainerExceptionInterface`, `NotFoundException` implements `NotFoundExceptionInterface` — both extend `Exception`.

**Tests** (`test/ContainerTest.php`) use food/house mock classes in `test/Mocks/` to cover: auto-wiring chains, interface mapping, union type errors, missing type hint errors, callable factories, and optional parameters.

## Known backlog (Linear)

Open issues in the **Dependency Injection** project, roughly priority order:

| ID | Title | Priority |
|----|-------|----------|
| ES-214 | No circular dependency detection (stack overflow on cycles) | High |
| ES-213 | `ReflectionIntersectionType` not handled — silently injects `null` | High |
| ES-212 | Scalar/built-in constructor params silently become `null` | High |
| ES-216 | `is_callable($entry)` is ambiguous — string function names treated as callables | Medium |
| ES-215 | Alias chains not followed — `set(A,B); set(B,C); get(A)` resolves B, not C | Medium |
| ES-217 | Variadic constructor parameters not handled | Medium |
| ES-219 | No instance caching — every `get()` rebuilds the full graph | Medium |
| ES-96  | Verify full PSR-11 compliance | Medium |
| ES-218 | Nullable/optional class-typed params always resolved, ignoring default | Low |
| ES-220 | `get()` missing return type and PHPDoc generics | Low |
| ES-221 | `@throws` annotations missing on public methods | Low |
| ES-222 | Dead code: empty `__construct`, `new $id()` vs `newInstance()` | Low |
| ES-223 | "not instantiable" message doesn't distinguish interfaces from abstracts | Low |

## Standards

- PHP 8.2+ required; CI tests against 8.2, 8.3, 8.4, 8.5
- PHPStan level 8 on `src/` and `test/` (excludes `test/Mocks/`)
- PSR-12 coding style enforced via phpcs
- `declare(strict_types=1)` in every file
