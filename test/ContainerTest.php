<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use stdClass;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;
use EntireStudio\DependencyInjection\Test\Mocks\D;
use EntireStudio\DependencyInjection\Test\Mocks\GreatInsulation;
use EntireStudio\DependencyInjection\Test\Mocks\House;
use EntireStudio\DependencyInjection\Test\Mocks\Insulation;
use EntireStudio\DependencyInjection\Test\Mocks\Lettuce;
use EntireStudio\DependencyInjection\Test\Mocks\OliveOil;
use EntireStudio\DependencyInjection\Test\Mocks\Salad;
use EntireStudio\DependencyInjection\Test\Mocks\Sandwich;
use EntireStudio\DependencyInjection\Test\Mocks\Snack;

class ContainerTest extends TestCase
{
    private function getContainer(): Container
    {
        return new Container();
    }

    public function testExceptionIsThrownOnNotFoundClass(): void
    {
        $this->expectException(NotFoundException::class);

        $container = $this->getContainer();
        $container->get('NonExistentClass');
    }

    public function testCanCreateBuiltInClass(): void
    {
        $container = $this->getContainer();
        $instance = $container->get('stdClass');

        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testAutowireChainedClasses(): void
    {
        $container = $this->getContainer();
        $instance = $container->get(D::class);

        $this->assertInstanceOf(D::class, $instance);
    }

    public function testAutowireChainedClassesInterfacesThrowExceptionWhenNotMapped(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' is not instantiable.');

        $container = $this->getContainer();
        $house = $container->get(House::class);

        var_dump($house);
    }

    public function testAutowireChainedClassesInterfaces(): void
    {
        $container = $this->getContainer();
        // Direct requirement to constructed class
        $container->set(Base::class, ConcreteBase::class);
        // Indirect requirement to constructed class
        $container->set(Insulation::class, GreatInsulation::class);
        $instance = $container->get(House::class);

        $this->assertInstanceOf(House::class, $instance);
    }

    public function testUnionThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' because of union type for param ');

        $container = $this->getContainer();
        $container->get(Sandwich::class);
    }

    public function testOptional(): void
    {
        $container = $this->getContainer();
        $instance = $container->get(Salad::class);

        $this->assertInstanceOf(Salad::class, $instance);
    }

    public function testOptionalCanBeMappedToIgnore(): void
    {
        $container = $this->getContainer();
        $container->set(
            Salad::class,
            fn(Container $c) => new Salad(
                $c->get(Lettuce::class),
                $c->get(OliveOil::class)
            )
        );
        $instance = $container->get(Salad::class);

        $this->assertInstanceOf(Salad::class, $instance);
    }

    public function testNoTypeHintsThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' is missing a type hint.');

        $container = $this->getContainer();
        $container->get(Snack::class);
    }
}
