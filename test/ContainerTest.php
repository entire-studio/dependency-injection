<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;
use EntireStudio\DependencyInjection\Test\Mocks\AbstractInsulation;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\Beer;
use EntireStudio\DependencyInjection\Test\Mocks\Bread;
use EntireStudio\DependencyInjection\Test\Mocks\Buffet;
use EntireStudio\DependencyInjection\Test\Mocks\Chicken;
use EntireStudio\DependencyInjection\Test\Mocks\Cocktail;
use EntireStudio\DependencyInjection\Test\Mocks\Color;
use EntireStudio\DependencyInjection\Test\Mocks\Loggable;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;
use EntireStudio\DependencyInjection\Test\Mocks\D;
use EntireStudio\DependencyInjection\Test\Mocks\HasMissingDep;
use EntireStudio\DependencyInjection\Test\Mocks\Hen;
use EntireStudio\DependencyInjection\Test\Mocks\Loop1;
use EntireStudio\DependencyInjection\Test\Mocks\GreatInsulation;
use EntireStudio\DependencyInjection\Test\Mocks\House;
use EntireStudio\DependencyInjection\Test\Mocks\Insulation;
use EntireStudio\DependencyInjection\Test\Mocks\Lettuce;
use EntireStudio\DependencyInjection\Test\Mocks\NeedsContainer;
use EntireStudio\DependencyInjection\Test\Mocks\ParentReferencing;
use EntireStudio\DependencyInjection\Test\Mocks\SelfReferencing;
use EntireStudio\DependencyInjection\Test\Mocks\OliveOil;
use EntireStudio\DependencyInjection\Test\Mocks\Pizza;
use EntireStudio\DependencyInjection\Test\Mocks\Pizzeria;
use EntireStudio\DependencyInjection\Test\Mocks\Salad;
use EntireStudio\DependencyInjection\Test\Mocks\Sandwich;
use EntireStudio\DependencyInjection\Test\Mocks\SandwichWithDefault;
use EntireStudio\DependencyInjection\Test\Mocks\Snack;
use EntireStudio\DependencyInjection\Test\Mocks\Vodka;

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

    public function testIntersectionThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' because of intersection type for param ');

        $container = $this->getContainer();
        $container->get(Pizza::class);
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

    public function testNoTypeHintsThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(' is missing a type hint.');

        $container = $this->getContainer();
        $container->get(Snack::class);
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

    public function testStringEntryMatchingGlobalFunctionIsTreatedAsClassRedirect(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Class "strlen" does not exist.');

        $container = $this->getContainer();
        $container->set('alias', 'strlen');
        $container->get('alias');
    }

    public function testMultiStepAliasChainIsFollowed(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, 'intermediate');
        $container->set('intermediate', ConcreteBase::class);

        $instance = $container->get(Base::class);

        $this->assertInstanceOf(ConcreteBase::class, $instance);
    }

    public function testAliasCycleThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Alias cycle detected');

        $container = $this->getContainer();
        $container->set('a', 'b');
        $container->set('b', 'a');
        $container->get('a');
    }

    public function testDirectSelfAliasThrowsException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Alias cycle detected');

        $container = $this->getContainer();
        $container->set('a', 'a');
        $container->get('a');
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

    public function testAutowiredInstancesAreCachedAsSingletons(): void
    {
        $container = $this->getContainer();

        $this->assertSame($container->get(Bread::class), $container->get(Bread::class));
    }

    public function testAliasedInstancesAreCachedByRequestedId(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);

        $this->assertSame($container->get(Base::class), $container->get(Base::class));
    }

    public function testClosureInstancesAreCachedAsSingletons(): void
    {
        $container = $this->getContainer();
        $container->set(Bread::class, fn() => new Bread());

        $this->assertSame($container->get(Bread::class), $container->get(Bread::class));
    }

    public function testFactoryReturnsFreshInstancesEachCall(): void
    {
        $container = $this->getContainer();
        $container->factory(Bread::class, fn() => new Bread());

        $this->assertNotSame($container->get(Bread::class), $container->get(Bread::class));
    }

    public function testReSetClearsCachedInstance(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);
        $first = $container->get(Base::class);

        $container->set(Base::class, ConcreteBase::class);
        $second = $container->get(Base::class);

        $this->assertNotSame($first, $second);
    }

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

    public function testValueReturnsScalarLiteral(): void
    {
        $container = $this->getContainer();
        $container->value('db.dsn', 'sqlite::memory:');

        $this->assertSame('sqlite::memory:', $container->get('db.dsn'));
        $this->assertTrue($container->has('db.dsn'));
    }

    public function testValueReturnsArrayLiteral(): void
    {
        $container = $this->getContainer();
        $container->value('app.flags', ['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $container->get('app.flags'));
    }

    public function testValueReturnsObjectInstanceUnchanged(): void
    {
        $container = $this->getContainer();
        $instance = new Bread();
        $container->value(Bread::class, $instance);

        $this->assertSame($instance, $container->get(Bread::class));
    }

    public function testValueReturnsNull(): void
    {
        $container = $this->getContainer();
        $container->value('maybe', null);

        $this->assertNull($container->get('maybe'));
        $this->assertTrue($container->has('maybe'));
    }

    public function testValueOverridesPriorSet(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);
        $sentinel = new ConcreteBase();
        $container->value(Base::class, $sentinel);

        $this->assertSame($sentinel, $container->get(Base::class));
    }

    public function testUnsetRemovesBinding(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);
        $container->get(Base::class);

        $container->unset(Base::class);

        $this->assertFalse($container->has(Base::class));
    }

    public function testUnsetClearsCachedInstanceSoNextGetReautowires(): void
    {
        $container = $this->getContainer();
        $first = $container->get(Bread::class);

        $container->unset(Bread::class);
        $second = $container->get(Bread::class);

        $this->assertNotSame($first, $second);
    }

    public function testClearDropsAllUserBindings(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, ConcreteBase::class);
        $container->value('flag', true);
        $container->get(Bread::class);

        $container->clear();

        $this->assertFalse($container->has(Base::class));
        $this->assertFalse($container->has('flag'));
    }

    public function testClearPreservesSelfRegistration(): void
    {
        $container = $this->getContainer();
        $container->clear();

        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($container, $container->get(Container::class));
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

    public function testSelfTypeHintThrowsDescriptiveException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot autowire "self" type hint');

        $container = $this->getContainer();
        $container->get(SelfReferencing::class);
    }

    public function testParentTypeHintThrowsDescriptiveException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot autowire "parent" type hint');

        $container = $this->getContainer();
        $container->get(ParentReferencing::class);
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

    public function testUnionTypeWithDefaultUsesDefault(): void
    {
        $container = $this->getContainer();
        $sandwich = $container->get(SandwichWithDefault::class);

        $this->assertInstanceOf(SandwichWithDefault::class, $sandwich);
        $this->assertNull($sandwich->topping);
    }

    public function testAliasesToSameTargetProduceDistinctInstancesByDesign(): void
    {
        $container = $this->getContainer();
        $container->set('logger.a', ConcreteBase::class);
        $container->set('logger.b', ConcreteBase::class);

        $this->assertNotSame($container->get('logger.a'), $container->get('logger.b'));
    }

    public function testClosureDelegationSharesInstanceAcrossAliases(): void
    {
        $container = $this->getContainer();
        $container->set('logger.a', fn(Container $c) => $c->get(ConcreteBase::class));
        $container->set('logger.b', fn(Container $c) => $c->get(ConcreteBase::class));

        $this->assertSame($container->get('logger.a'), $container->get('logger.b'));
    }

    public function testLeadingBackslashIsNormalizedAcrossSetAndGet(): void
    {
        $container = $this->getContainer();
        $container->set('\\' . Base::class, ConcreteBase::class);

        $this->assertTrue($container->has(Base::class));
        $this->assertTrue($container->has('\\' . Base::class));
        $this->assertInstanceOf(ConcreteBase::class, $container->get(Base::class));
    }

    public function testLeadingBackslashNormalizedInConcreteTarget(): void
    {
        $container = $this->getContainer();
        $container->set(Base::class, '\\' . ConcreteBase::class);

        $this->assertInstanceOf(ConcreteBase::class, $container->get(Base::class));
    }

    public function testLeadingBackslashNormalizedInUnsetAndValue(): void
    {
        $container = $this->getContainer();
        $container->value('\\foo', 42);

        $this->assertSame(42, $container->get('foo'));

        $container->unset('\\foo');

        $this->assertFalse($container->has('foo'));
    }
}
