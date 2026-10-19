<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class SandwichWithDefault
{
    public function __construct(
        public readonly Cheese|Ham|null $topping = null,
    ) {
    }
}
