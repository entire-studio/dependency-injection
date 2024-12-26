# Dependency Injection

![Packagist Version (including pre-releases)](https://img.shields.io/packagist/v/entire-studio/dependency-injection?include_prereleases)
![GitHub release (latest SemVer including pre-releases)](https://img.shields.io/github/v/release/entire-studio/dependency-injection?include_prereleases&sort=semver)
[![CI](https://github.com/entire-studio/dependency-injection/actions/workflows/ci.yml/badge.svg)](https://github.com/entire-studio/dependency-injection/actions/workflows/ci.yml)
[![codecov](https://codecov.io/github/entire-studio/dependency-injection/branch/master/graph/badge.svg?token=NTODzYRsCX)](https://codecov.io/github/entire-studio/dependency-injection)

PSR-11 compatible dependency injection container

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

## Commands

### Development
- `composer test` - runs test suite.
- `composer sat` - runs static analysis.
- `composer style` - checks codebase against PSR-12 coding style.
- `composer style:fix` - fixes basic coding style issues.
