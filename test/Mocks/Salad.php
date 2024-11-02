<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Salad
{
    public function __construct(
        private readonly Lettuce $lettuce,
        private readonly OliveOil $oliveOil,
        private readonly ?Chicken $chicken = null,
    ) {
    }
}
