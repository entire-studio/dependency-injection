<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Pizzeria
{
    /** @var array<int, Cheese> */
    public readonly array $extraCheeses;

    public function __construct(Cheese ...$extraCheeses)
    {
        $this->extraCheeses = $extraCheeses;
    }
}
