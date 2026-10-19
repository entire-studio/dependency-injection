<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Cocktail
{
    public function __construct(
        public readonly ?string $name,
    ) {
    }
}
