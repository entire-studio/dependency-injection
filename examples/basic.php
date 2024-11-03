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
