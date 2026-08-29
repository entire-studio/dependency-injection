<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Salad
{
    public function __construct(
        public readonly Lettuce $lettuce,
        public readonly OliveOil $oliveOil,
        public readonly ?Chicken $chicken = null,
    ) {
    }
}
