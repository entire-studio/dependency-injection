<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Loop1
{
    public function __construct(
        private readonly Loop2 $loop2,
    ) {
    }
}
