# Changelog

All notable changes to this project are documented in this file. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `value($id, mixed)` for literal/scalar bindings (ES-225).
- `factory($id, Closure)` opt-out for per-call instantiation (ES-219).
- `unset($id)` and `clear()` for removing bindings and resetting state (ES-227).
- `call(callable, array)` for invoking any callable with autowired arguments (ES-233).
- `injectMethod($id, method, args)` for setter injection (ES-237).
- `tag()` / `untag()` / `getTagged()` for grouping services (ES-234).
- `extend()` for stacking decorators on existing bindings (ES-235).
- `#[Inject(id)]` attribute to override parameter resolution (ES-236).
- Container self-registration under `ContainerInterface`, `self::class`,
  and `static::class` — autowireable as a constructor dependency (ES-224).
- `ReflectionClass` cache for repeated resolves (ES-240).
- PHPDoc generics on `get()` (ES-220) and `@throws` annotations on public methods (ES-221).

### Changed
- Singleton-by-default lifecycle — `get()` results are cached unless registered
  via `factory()`. Re-registration via `set()` / `factory()` / `value()`
  invalidates the cached instance (ES-219).
- Alias chains are followed transitively with cycle detection (ES-215).
- Class names are normalized: leading `\` is stripped across all id-accepting
  APIs (ES-238).
- Closure invocations that throw non-`ContainerExceptionInterface` exceptions
  are wrapped as `ContainerException` with the original as `previous` (ES-231).
- Inner missing-dependency `NotFoundException` is wrapped as `ContainerException`;
  only the top-level requested id can yield `NotFoundException` (ES-226).
- `resolveParameter` extracted into a dedicated `ParameterResolver` class (ES-241).
- Tests split into focused suites: `AutowiringTest`, `AliasTest`, `LifecycleTest`,
  `ErrorTest`, `Psr11Test` (ES-239).

### Fixed
- Built-in/scalar constructor params no longer silently become `null`. Uses
  default → null → throws `ContainerException` (ES-212).
- Circular dependencies are detected and throw `ContainerException` instead of
  causing a stack overflow (ES-214).
- `ReflectionIntersectionType` throws `ContainerException` like union types (ES-213).
- Variadic constructor parameters are skipped (passed as empty array) (ES-217).
- Nullable/optional class-typed params honor their default value (ES-218).
- Union/intersection types with a default value return the default instead of
  throwing (ES-230).
- `self` / `parent` / `static` type hints throw a descriptive `ContainerException`
  instead of attempting to resolve a literal `"self"` / `"parent"` / `"static"` (ES-228).
- Enums and traits throw specific messages instead of the generic
  "not instantiable" (ES-229).
- Unbound interfaces and abstract classes produce more specific error messages
  than "not instantiable" (ES-223).
- Closure-vs-string ambiguity in `$entries` — only `Closure` instances are
  invoked; strings matching global function names are treated as alias targets (ES-216).
- PSR-11 conformance: `ReflectionException` from `newInstance(Args)` is wrapped
  as `ContainerException` (ES-96).

## [1.2.1] - 2025-08-05

### Changed
- Dependabot upgrades for `phpstan/phpstan`, `squizlabs/php_codesniffer`,
  `dealerdirect/phpcodesniffer-composer-installer`, and `phpunit/phpunit`.

## [1.2.0] - 2024-12-26

### Added
- PHPStan configuration and CI step (ES-71, ES-72).
- Migration to `actions/upload-artifact@v4` / `download-artifact@v4` (ES-87).

### Changed
- PHPStan-driven refactor across `src/` to clear reported issues (ES-73).
- Dead-code removal in `Container.php`.

## [1.1.0] - 2024-11-03

### Added
- Expanded documentation and additional usage examples.

## [1.0.0] - 2024-11-03

### Added
- Initial release: PSR-11 compatible dependency injection container with
  `get()`, `has()`, and `set()` for alias and Closure bindings.

[Unreleased]: https://github.com/entire-studio/dependency-injection/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/entire-studio/dependency-injection/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/entire-studio/dependency-injection/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/entire-studio/dependency-injection/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/entire-studio/dependency-injection/releases/tag/v1.0.0
