<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class House
{
    public function __construct(
        private readonly Base $base,
        private readonly Wall $wall,
        private readonly Roof $roof,
    ) {
    }
}
