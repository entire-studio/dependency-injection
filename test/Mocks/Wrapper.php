<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Wrapper
{
    public function __construct(
        public readonly string $layer,
        public readonly object $inner,
    ) {
    }
}
