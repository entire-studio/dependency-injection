<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Sandwich
{
    public function __construct(
        private readonly Bread $bread,
        private readonly Butter $butter,
        private readonly Cheese|Ham $cheeseOrHam,
    ) {
    }
}
