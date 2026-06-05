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
    ) {}
}

$di = new Container();
$di->set(Wall::class, WoodenWall::class);

$house = $di->get(House::class);

echo get_class($house->mainWall) . PHP_EOL;   // WoodenWall
echo get_class($house->otherWalls) . PHP_EOL; // ConcreteWall
