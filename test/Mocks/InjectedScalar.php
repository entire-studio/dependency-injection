<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

use EntireStudio\DependencyInjection\Attributes\Inject;

class InjectedScalar
{
    public function __construct(
        #[Inject('db.dsn')]
        public readonly string $dsn,
    ) {
    }
}
