<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

use EntireStudio\DependencyInjection\Container;
use Psr\Container\ContainerInterface;

class NeedsContainer
{
    public function __construct(
        public readonly ContainerInterface $psr,
        public readonly Container $concrete,
    ) {
    }
}
