<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

interface Wall {}
class WoodenWall implements Wall {}
class ConcreteWall implements Wall {}

class House
{
    public function __construct(
        public readonly Wall $mainWall,
        public readonly ConcreteWall $otherWalls,
        public readonly int $numberOfWalls,
    ) {}
}

$di = new Container();
$di->set(Wall::class, WoodenWall::class);
$di->set(House::class, fn(Container $di) => new House(
    $di->get(Wall::class),
    $di->get(ConcreteWall::class),
    4,
));

$house = $di->get(House::class);

echo get_class($house->mainWall) . PHP_EOL;   // WoodenWall
echo get_class($house->otherWalls) . PHP_EOL; // ConcreteWall
echo $house->numberOfWalls . PHP_EOL;          // 4
