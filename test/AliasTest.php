<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;

class AliasTest extends ContainerTestCase
{
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
