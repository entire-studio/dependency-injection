<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Wall
{
    public function __construct(
        private readonly InnerSide $innerSide,
        private readonly Insulation $insulation,
        private readonly OuterSide $outerSide,
    ) {
    }
}
