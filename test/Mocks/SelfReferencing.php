<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class SelfReferencing
{
    public function __construct(
        private readonly self $other,
    ) {
    }
}
