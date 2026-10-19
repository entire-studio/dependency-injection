<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Vodka
{
    public function __construct(
        public readonly int $abv,
    ) {
    }
}
