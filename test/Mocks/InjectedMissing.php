<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

use EntireStudio\DependencyInjection\Attributes\Inject;

class InjectedMissing
{
    public function __construct(
        #[Inject('does.not.exist')]
        public readonly string $value,
    ) {
    }
}
