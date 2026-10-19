<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\Bread;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;
use EntireStudio\DependencyInjection\Test\Mocks\NeedsContainer;
use Psr\Container\ContainerInterface;

class Psr11Test extends ContainerTestCase
{
    public function testHasReturnsFalseForUnregisteredAutowirableClass(): void
    {
        $container = $this->getContainer();

        $this->assertFalse($container->has(Bread::class));
        $this->assertInstanceOf(Bread::class, $container->get(Bread::class));
    }

    public function testHasReturnsTrueOnlyForRegisteredEntries(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);

        $this->assertTrue($container->has(Base::class));
        $this->assertFalse($container->has(ConcreteBase::class));
    }

    public function testGetContainerInterfaceReturnsSelf(): void
    {
        $container = $this->getContainer();

        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testGetContainerClassReturnsSelf(): void
    {
        $container = $this->getContainer();

        $this->assertSame($container, $container->get(Container::class));
    }

    public function testContainerIsAutowiredAsConstructorDependency(): void
    {
        $container = $this->getContainer();
        $needs = $container->get(NeedsContainer::class);

        $this->assertSame($container, $needs->psr);
        $this->assertSame($container, $needs->concrete);
    }

    public function testClearPreservesSelfRegistration(): void
    {
        $container = $this->getContainer();
        $container->clear();

        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($container, $container->get(Container::class));
    }
}
