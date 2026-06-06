<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\Beer;
use EntireStudio\DependencyInjection\Test\Mocks\Bread;
use EntireStudio\DependencyInjection\Test\Mocks\Buffet;
use EntireStudio\DependencyInjection\Test\Mocks\Chicken;
use EntireStudio\DependencyInjection\Test\Mocks\Cocktail;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;
use EntireStudio\DependencyInjection\Test\Mocks\Configurable;
use EntireStudio\DependencyInjection\Test\Mocks\D;
use EntireStudio\DependencyInjection\Test\Mocks\GreatInsulation;
use EntireStudio\DependencyInjection\Test\Mocks\Greeter;
use EntireStudio\DependencyInjection\Test\Mocks\House;
use EntireStudio\DependencyInjection\Test\Mocks\InjectedClass;
use EntireStudio\DependencyInjection\Test\Mocks\InjectedScalar;
use EntireStudio\DependencyInjection\Test\Mocks\Insulation;
use EntireStudio\DependencyInjection\Test\Mocks\Lettuce;
use EntireStudio\DependencyInjection\Test\Mocks\OliveOil;
use EntireStudio\DependencyInjection\Test\Mocks\Pizzeria;
use EntireStudio\DependencyInjection\Test\Mocks\Salad;
use EntireStudio\DependencyInjection\Test\Mocks\SandwichWithDefault;
use stdClass;

class AutowiringTest extends ContainerTestCase
{
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

    public function testAutowireChainedClassesInterfaces(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);
        $container->set(Insulation::class, GreatInsulation::class);
        $instance = $container->get(House::class);

        $this->assertInstanceOf(House::class, $instance);
    }

    public function testNullableClassParamWithDefaultHonorsDefault(): void
    {
        $container = $this->getContainer();
        $salad = $container->get(Salad::class);

        $this->assertInstanceOf(Salad::class, $salad);
        $this->assertNull($salad->chicken);
    }

    public function testNullableClassParamCanBeOverriddenViaCallable(): void
    {
        $container = $this->getContainer();
        $container->set(
            Salad::class,
            fn(Container $c) => new Salad(
                $c->get(Lettuce::class),
                $c->get(OliveOil::class),
                $c->get(Chicken::class),
            )
        );
        $salad = $container->get(Salad::class);

        $this->assertInstanceOf(Salad::class, $salad);
        $this->assertInstanceOf(Chicken::class, $salad->chicken);
    }

    public function testBuiltinParamWithDefaultUsesDefault(): void
    {
        $container = $this->getContainer();
        $beer = $container->get(Beer::class);

        $this->assertInstanceOf(Beer::class, $beer);
        $this->assertSame(5, $beer->abv);
    }

    public function testNullableBuiltinParamWithoutDefaultBecomesNull(): void
    {
        $container = $this->getContainer();
        $cocktail = $container->get(Cocktail::class);

        $this->assertInstanceOf(Cocktail::class, $cocktail);
        $this->assertNull($cocktail->name);
    }

    public function testVariadicClassParamReceivesEmptyArray(): void
    {
        $container = $this->getContainer();
        $pizzeria = $container->get(Pizzeria::class);

        $this->assertInstanceOf(Pizzeria::class, $pizzeria);
        $this->assertSame([], $pizzeria->extraCheeses);
    }

    public function testVariadicScalarParamReceivesEmptyArray(): void
    {
        $container = $this->getContainer();
        $buffet = $container->get(Buffet::class);

        $this->assertInstanceOf(Buffet::class, $buffet);
        $this->assertInstanceOf(Bread::class, $buffet->bread);
        $this->assertSame([], $buffet->names);
    }

    public function testUnionTypeWithDefaultUsesDefault(): void
    {
        $container = $this->getContainer();
        $sandwich = $container->get(SandwichWithDefault::class);

        $this->assertInstanceOf(SandwichWithDefault::class, $sandwich);
        $this->assertNull($sandwich->topping);
    }

    public function testCallClosureAutowiresArguments(): void
    {
        $container = $this->getContainer();
        $result = $container->call(fn(Bread $b) => $b::class);

        $this->assertSame(Bread::class, $result);
    }

    public function testCallClosureWithExtraArgsOverridesAutowiring(): void
    {
        $container = $this->getContainer();
        $result = $container->call(
            fn(Bread $b, string $who = 'world') => $who,
            ['who' => 'tomek'],
        );

        $this->assertSame('tomek', $result);
    }

    public function testCallObjectMethod(): void
    {
        $container = $this->getContainer();
        $greeter = new Greeter();

        $result = $container->call([$greeter, 'greet'], ['who' => 'planet']);

        $this->assertSame('hello planet with ' . Bread::class, $result);
    }

    public function testCallStaticMethodViaArrayCallable(): void
    {
        $container = $this->getContainer();
        $result = $container->call([Greeter::class, 'staticGreet']);

        $this->assertSame('static hello ' . Bread::class, $result);
    }

    public function testCallStaticMethodViaStringCallable(): void
    {
        $container = $this->getContainer();
        $result = $container->call(Greeter::class . '::staticGreet');

        $this->assertSame('static hello ' . Bread::class, $result);
    }

    public function testCallInvokableObject(): void
    {
        $container = $this->getContainer();
        $greeter = new Greeter();

        $result = $container->call($greeter);

        $this->assertSame('invoked with ' . Bread::class, $result);
    }

    public function testCallResolvesAutowiredParamAfterNamedArg(): void
    {
        $container = $this->getContainer();

        $result = $container->call(
            fn(string $who, Bread $bread) => $who . ':' . $bread::class,
            ['who' => 'hi'],
        );

        $this->assertSame('hi:' . Bread::class, $result);
    }

    public function testCallSkipsVariadicParameter(): void
    {
        $container = $this->getContainer();

        $result = $container->call(
            fn(Bread $bread, string ...$rest) => $bread::class . ':' . count($rest),
        );

        $this->assertSame(Bread::class . ':0', $result);
    }

    public function testSingleSetterIsInvokedAfterConstruction(): void
    {
        $container = $this->getContainer();
        $container->injectMethod(Configurable::class, 'setBread');

        $configurable = $container->get(Configurable::class);

        $this->assertInstanceOf(Bread::class, $configurable->bread);
        $this->assertSame(['bread'], $configurable->log);
    }

    public function testMultipleSettersAreInvokedInRegistrationOrder(): void
    {
        $container = $this->getContainer();
        $container->injectMethod(Configurable::class, 'setLabel');
        $container->injectMethod(Configurable::class, 'setBread');

        $configurable = $container->get(Configurable::class);

        $this->assertSame(['label:default', 'bread'], $configurable->log);
    }

    public function testSetterArgsOverrideAutowiring(): void
    {
        $container = $this->getContainer();
        $container->injectMethod(Configurable::class, 'setLabel', ['label' => 'custom']);

        $configurable = $container->get(Configurable::class);

        $this->assertSame(['label:custom'], $configurable->log);
    }

    public function testInjectAttributeOverridesScalarAutowiring(): void
    {
        $container = $this->getContainer();
        $container->value('db.dsn', 'sqlite::memory:');

        $instance = $container->get(InjectedScalar::class);

        $this->assertSame('sqlite::memory:', $instance->dsn);
    }

    public function testInjectAttributeOverridesClassAutowiring(): void
    {
        $container = $this->getContainer();
        $chosen = new ConcreteBase();
        $container->value('chosen.base', $chosen);

        $instance = $container->get(InjectedClass::class);

        $this->assertSame($chosen, $instance->base);
    }
}
