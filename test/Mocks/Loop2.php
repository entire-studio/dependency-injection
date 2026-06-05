<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Loop2
{
    public function __construct(
        private readonly Loop3 $loop3,
    ) {
    }
}
