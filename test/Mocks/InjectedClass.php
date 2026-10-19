<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

use EntireStudio\DependencyInjection\Attributes\Inject;

class InjectedClass
{
    public function __construct(
        #[Inject('chosen.base')]
        public readonly Base $base,
    ) {
    }
}
