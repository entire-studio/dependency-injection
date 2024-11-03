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

// A class with three properties, one of which is concrete, second one is an interface and the third one being an integer
readonly class House {
    public function __construct(
        private Wall $mainWall,
        private ConcreteWall $otherWalls,
        private int $numberOfWalls,
    ) {}

    public function getWalls(): string {
        return sprintf(
            '%s: %s' . PHP_EOL .
            '%s: %s' . PHP_EOL .
            '%s: %d' . PHP_EOL,
            'Main wall',
            get_class($this->mainWall),
            'Other walls',
            get_class($this->otherWalls),
            '# of walls',
            $this->numberOfWalls,
        );
    }
}

// Map the Wall interface to WoodenWall
$di->set(Wall::class, WoodenWall::class); // Without this we wouldn't know whether to use Wooden or Concrete wall in place of the Wall interface

$di->set(House::class, fn(Container $di) => new House( // This time we need to register House to be able to provide the last parameter
    $di->get(Wall::class), // Let DI figure out which concrete class to use for this interface (we registered it above)
    $di->get(ConcreteWall::class), // Even though it was not registered directly - it'll work
    42,
));

// Get the instance and call the class method
echo $di->get(House::class)->getWalls();

/*
 * Main wall: WoodenWall
 * Other walls: ConcreteWall
 * # of walls: 42
 */
