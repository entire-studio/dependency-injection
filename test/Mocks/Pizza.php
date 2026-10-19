<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Pizza
{
    public function __construct(
        private readonly Countable&Stringable $topping,
    ) {
    }
}
