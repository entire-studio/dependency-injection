<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Loop3
{
    public function __construct(
        private readonly Loop1 $loop1,
    ) {
    }
}
