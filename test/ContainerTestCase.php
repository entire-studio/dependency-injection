<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;

abstract class ContainerTestCase extends TestCase
{
    protected function getContainer(): Container
    {
        return new Container();
    }
}
