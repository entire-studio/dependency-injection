# Dependency Injection

![Packagist Version (including pre-releases)](https://img.shields.io/packagist/v/entire-studio/dependency-injection?include_prereleases)
![GitHub release (latest SemVer including pre-releases)](https://img.shields.io/github/v/release/entire-studio/dependency-injection?include_prereleases&sort=semver)
![PHP](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue)
[![CI](https://github.com/entire-studio/dependency-injection/actions/workflows/ci.yml/badge.svg)](https://github.com/entire-studio/dependency-injection/actions/workflows/ci.yml)
[![codecov](https://codecov.io/github/entire-studio/dependency-injection/branch/master/graph/badge.svg?token=NTODzYRsCX)](https://codecov.io/github/entire-studio/dependency-injection)

PSR-11 compatible dependency injection container. Requires PHP 8.2+.

## Contents

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Other examples](#other-examples)
- [PSR-11](#psr-11)
- [Lifecycle](#lifecycle)
  - [Aliases and sharing](#aliases-and-sharing)
- [Commands](#commands)
- [Changelog](#changelog)

## Installation
Install the latest version with
```bash
$ composer require entire-studio/dependency-injection
```

## Basic Usage
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

// Create a DI container
$di = new Container();

// Two concrete implementations with a common interface
interface Wall {}
class WoodenWall implements Wall {}
class ConcreteWall implements Wall {}

// A class with two properties, one of which is concrete and the other one is an interface
readonly class House {
    public function __construct(
        private Wall $mainWall,
        private ConcreteWall $otherWalls,
    ) {}

    public function getWallClass(): string {
        return sprintf(
            '%s: %s' . PHP_EOL .
            '%s: %s' . PHP_EOL,
            'Main wall',
            get_class($this->mainWall),
            'Other walls',
            get_class($this->otherWalls),
        );
    }
}

// Map the Wall interface to WoodenWall
$di->set(Wall::class, WoodenWall::class); // Without this we wouldn't know whether to use Wooden or Concrete wall in place of the Wall interface

// $di->set(House::class, House::class);  // No need to register House as with interface mapped - we can reflect other params

// Get the instance
$house = $di->get(House::class);

// Call the class method
echo $house->getWallClass();

/*
 * Main wall: WoodenWall
 * Other walls: ConcreteWall
 */

```
```bash
$ php examples/basic.php
```

## Other examples
See `examples/` for more examples.

## PSR-11

The container implements `Psr\Container\ContainerInterface` (psr/container ^2.0).

- `get(string $id): mixed` — returns the entry. Throws `NotFoundExceptionInterface`
  when no entry exists for `$id` (and `$id` isn't an instantiable class), or
  `ContainerExceptionInterface` for any other failure during construction.
- `has(string $id): bool` — returns true only for ids previously registered via
  `set()` / `factory()`. Per the spec, `has() === false` does not preclude `get()`
  from succeeding (autowiring may still resolve it), and `has() === true` does
  not guarantee `get()` will not throw.

The container self-registers under `Psr\Container\ContainerInterface` and its own
concrete class — `get(ContainerInterface::class)` and `get(Container::class)`
both return `$this`, and constructor parameters of either type are auto-wired
to the active container.

## Lifecycle

By default, the container caches resolved instances — subsequent `get($id)` calls
return the same object (singleton-by-default).

To get a fresh instance on every `get()`, register a factory:

```php
$di->factory(Clock::class, fn() => new SystemClock());
$a = $di->get(Clock::class);
$b = $di->get(Clock::class);
// $a !== $b
```

To register a literal value (config string, array, pre-built object), use `value()`:

```php
$di->value('db.dsn', 'sqlite::memory:');
$di->get('db.dsn'); // 'sqlite::memory:'
```

Re-registering with `set()`, `factory()`, or `value()` clears any cached state for that id.

`unset($id)` drops a single binding (entry + cached instance + factory marker), and
`clear()` resets all user state; the container's self-registration is preserved.

### Aliases and sharing

Singleton caching is keyed on the **requested** id, not the resolved target. Two
aliases pointing at the same concrete class produce two distinct instances:

```php
$di->set(LoggerA::class, FileLogger::class);
$di->set(LoggerB::class, FileLogger::class);
$di->get(LoggerA::class); // FileLogger instance #1
$di->get(LoggerB::class); // FileLogger instance #2 — not shared
```

To share a single instance across aliases, register the concrete first and have
the aliases delegate to it via a Closure:

```php
$di->set(LoggerA::class, fn(Container $c) => $c->get(FileLogger::class));
$di->set(LoggerB::class, fn(Container $c) => $c->get(FileLogger::class));
// both now resolve to the same FileLogger singleton
```

## Commands

### Development
- `composer test` - runs test suite.
- `composer sat` - runs static analysis.
- `composer style` - checks codebase against PSR-12 coding style.
- `composer style:fix` - fixes basic coding style issues.

## Changelog
See [CHANGELOG.md](CHANGELOG.md) for release history.
