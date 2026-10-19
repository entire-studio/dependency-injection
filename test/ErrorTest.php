<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use EntireStudio\DependencyInjection\Test\Mocks\AbstractInsulation;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\Bread;
use EntireStudio\DependencyInjection\Test\Mocks\Color;
use EntireStudio\DependencyInjection\Test\Mocks\HasMissingDep;
use EntireStudio\DependencyInjection\Test\Mocks\Hen;
use EntireStudio\DependencyInjection\Test\Mocks\House;
use EntireStudio\DependencyInjection\Test\Mocks\InjectedMissing;
use EntireStudio\DependencyInjection\Test\Mocks\Loggable;
use EntireStudio\DependencyInjection\Test\Mocks\Loop1;
use EntireStudio\DependencyInjection\Test\Mocks\ParentReferencing;
use EntireStudio\DependencyInjection\Test\Mocks\Pizza;
use EntireStudio\DependencyInjection\Test\Mocks\PrivateConstructor;
use EntireStudio\DependencyInjection\Test\Mocks\Sandwich;
use EntireStudio\DependencyInjection\Test\Mocks\SelfReferencing;
use EntireStudio\DependencyInjection\Test\Mocks\Snack;
use EntireStudio\DependencyInjection\Test\Mocks\Vodka;
use RuntimeException;

class ErrorTest extends ContainerTestCase
{
    public function testExceptionIsThrownOnNotFoundClass(): void
    {
        $this->expectException(NotFoundException::class);

        $container = $this->getContainer();
        $container->get('NonExistentClass');
    }

    public function testAutowireChainedClassesInterfacesThrowExceptionWhenNotMapped(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('No binding registered for interface ');

        $container = $this->getContainer();
        $container->get(House::class);
    }

    public function testUnboundInterfaceThrowsSpecificException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('No binding registered for interface "' . Base::class . '".');

        $container = $this->getContainer();
        $container->get(Base::class);
    }

    public function testUnboundAbstractClassThrowsSpecificException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            'No binding registered for abstract class "' . AbstractInsulation::class . '".'
        );

        $container = $this->getContainer();
        $container->get(AbstractInsulation::class);
    }

    public function testUnionThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' because of union type for param ');

        $container = $this->getContainer();
        $container->get(Sandwich::class);
    }

    public function testIntersectionThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' because of intersection type for param ');

        $container = $this->getContainer();
        $container->get(Pizza::class);
    }

    public function testNoTypeHintsThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' is missing a type hint.');

        $container = $this->getContainer();
        $container->get(Snack::class);
    }

    public function testRequiredBuiltinParamThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('has built-in type "int" and no default value');

        $container = $this->getContainer();
        $container->get(Vodka::class);
    }

    public function testDirectCircularDependencyThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $container = $this->getContainer();
        $container->get(Hen::class);
    }

    public function testIndirectCircularDependencyThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $container = $this->getContainer();
        $container->get(Loop1::class);
    }

    public function testClosureRuntimeExceptionIsWrappedInContainerException(): void
    {
        $container = $this->getContainer();
        $container->set(Bread::class, function (): never {
            throw new RuntimeException('boom');
        });

        try {
            $container->get(Bread::class);
            $this->fail('Expected ContainerException');
        } catch (ContainerException $e) {
            $this->assertStringContainsString('Closure binding for', $e->getMessage());
            $this->assertStringContainsString('boom', $e->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $e->getPrevious());
        }
    }

    public function testClosureContainerExceptionPassesThroughUnwrapped(): void
    {
        $container = $this->getContainer();
        $original = new ContainerException('original');
        $container->set(Bread::class, function () use ($original): never {
            throw $original;
        });

        try {
            $container->get(Bread::class);
            $this->fail('Expected ContainerException');
        } catch (ContainerException $e) {
            $this->assertSame($original, $e);
        }
    }

    public function testClosureNotFoundExceptionPassesThroughUnwrapped(): void
    {
        $container = $this->getContainer();
        $original = new NotFoundException('not found');
        $container->set(Bread::class, function () use ($original): never {
            throw $original;
        });

        try {
            $container->get(Bread::class);
            $this->fail('Expected NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame($original, $e);
        }
    }

    public function testCircularDependencyResolvingIsClearedAfterThrow(): void
    {
        $container = $this->getContainer();

        try {
            $container->get(Hen::class);
            $this->fail('Expected ContainerException');
        } catch (ContainerException) {
            // expected
        }

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        $container->get(Hen::class);
    }

    public function testMethodInjectionWithUncallableMethodThrows(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Method injection for');
        $this->expectExceptionMessage('is not callable');

        $container = $this->getContainer();
        $container->injectMethod(Bread::class, 'nonExistentMethod');
        $container->get(Bread::class);
    }

    public function testInjectAttributeMissingIdMessageMentionsAttribute(): void
    {
        $container = $this->getContainer();

        try {
            $container->get(InjectedMissing::class);
            $this->fail('Expected ContainerException');
        } catch (ContainerException $e) {
            $this->assertStringContainsString('#[Inject(', $e->getMessage());
        }
    }

    public function testInnerMissingDependencyYieldsContainerException(): void
    {
        $container = $this->getContainer();

        try {
            $container->get(HasMissingDep::class);
            $this->fail('Expected ContainerException');
        } catch (ContainerException $e) {
            $this->assertStringContainsString('dependency', $e->getMessage());
            $this->assertInstanceOf(NotFoundException::class, $e->getPrevious());
        }
    }

    public function testTopLevelMissingClassStillYieldsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $container = $this->getContainer();
        $container->get('NonExistentTopLevelClass');
    }

    public function testSelfTypeHintThrowsContainerException(): void
    {
        $this->expectException(ContainerException::class);

        $container = $this->getContainer();
        $container->get(SelfReferencing::class);
    }

    public function testParentTypeHintThrowsContainerException(): void
    {
        $this->expectException(ContainerException::class);

        $container = $this->getContainer();
        $container->get(ParentReferencing::class);
    }

    public function testClassWithPrivateConstructorIsNotInstantiable(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class "' . PrivateConstructor::class . '" is not instantiable.');

        $container = $this->getContainer();
        $container->get(PrivateConstructor::class);
    }

    public function testEnumThrowsSpecificException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot autowire enum "' . Color::class . '"');

        $container = $this->getContainer();
        $container->get(Color::class);
    }

    public function testTraitThrowsSpecificException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot autowire trait "' . Loggable::class . '"');

        $container = $this->getContainer();
        $container->get(Loggable::class);
    }

    public function testExtendWithoutBindingThrows(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot extend "unbound": no binding registered.');

        $container = $this->getContainer();
        $container->extend('unbound', fn(mixed $i) => $i);
    }

    public function testInjectAttributeMissingIdYieldsContainerException(): void
    {
        $this->expectException(ContainerException::class);

        $container = $this->getContainer();
        $container->get(InjectedMissing::class);
    }
}
