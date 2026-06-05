<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Egg
{
    public function __construct(
        private readonly Hen $hen,
    ) {
    }
}
