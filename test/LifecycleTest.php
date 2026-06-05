<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test;

use EntireStudio\DependencyInjection\Test\Mocks\Base;
use EntireStudio\DependencyInjection\Test\Mocks\Bread;
use EntireStudio\DependencyInjection\Test\Mocks\Cheese;
use EntireStudio\DependencyInjection\Test\Mocks\ConcreteBase;
use EntireStudio\DependencyInjection\Test\Mocks\Wrapper;

class LifecycleTest extends ContainerTestCase
{
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

    public function testTagGroupsServicesByLabel(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');
        $container->tag(Cheese::class, 'food');

        $tagged = $container->getTagged('food');

        $this->assertCount(2, $tagged);
        $this->assertInstanceOf(Bread::class, $tagged[0]);
        $this->assertInstanceOf(Cheese::class, $tagged[1]);
    }

    public function testTagReturnsInstancesInRegistrationOrder(): void
    {
        $container = $this->getContainer();
        $container->tag(Cheese::class, 'food');
        $container->tag(Bread::class, 'food');

        $tagged = $container->getTagged('food');

        $this->assertInstanceOf(Cheese::class, $tagged[0]);
        $this->assertInstanceOf(Bread::class, $tagged[1]);
    }

    public function testTagSupportsMultipleTagsPerService(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');
        $container->tag(Bread::class, 'carb');

        $this->assertCount(1, $container->getTagged('food'));
        $this->assertCount(1, $container->getTagged('carb'));
    }

    public function testDuplicateTagRegistrationIsIgnored(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');
        $container->tag(Bread::class, 'food');

        $this->assertCount(1, $container->getTagged('food'));
    }

    public function testUntagRemovesAssociation(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');
        $container->tag(Cheese::class, 'food');

        $container->untag(Bread::class, 'food');

        $tagged = $container->getTagged('food');
        $this->assertCount(1, $tagged);
        $this->assertInstanceOf(Cheese::class, $tagged[0]);
    }

    public function testGetTaggedReturnsEmptyForUnknownTag(): void
    {
        $container = $this->getContainer();

        $this->assertSame([], $container->getTagged('nope'));
    }

    public function testUnsetAlsoRemovesFromAllTags(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');
        $container->tag(Bread::class, 'carb');

        $container->unset(Bread::class);

        $this->assertSame([], $container->getTagged('food'));
        $this->assertSame([], $container->getTagged('carb'));
    }

    public function testClearWipesAllTags(): void
    {
        $container = $this->getContainer();
        $container->tag(Bread::class, 'food');

        $container->clear();

        $this->assertSame([], $container->getTagged('food'));
    }

    public function testExtendWrapsResolvedInstance(): void
    {
        $container = $this->getContainer();
        $container->set('logger', Bread::class);
        $container->extend('logger', fn(Bread $inner) => new Wrapper('only', $inner));

        $result = $container->get('logger');

        $this->assertInstanceOf(Wrapper::class, $result);
        $this->assertInstanceOf(Bread::class, $result->inner);
    }

    public function testExtendStacksInRegistrationOrder(): void
    {
        $container = $this->getContainer();
        $container->set('thing', Bread::class);
        $container->extend('thing', fn(object $inner) => new Wrapper('first', $inner));
        $container->extend('thing', fn(Wrapper $inner) => new Wrapper('second', $inner));

        $result = $container->get('thing');

        $this->assertInstanceOf(Wrapper::class, $result);
        $this->assertSame('second', $result->layer);
        $this->assertInstanceOf(Wrapper::class, $result->inner);
        $this->assertSame('first', $result->inner->layer);
        $this->assertInstanceOf(Bread::class, $result->inner->inner);
    }

    public function testExtendOnFactoryRunsPerCall(): void
    {
        $container = $this->getContainer();
        $counter = 0;
        $container->factory('thing', fn() => new Bread());
        $container->extend('thing', function (Bread $inner) use (&$counter) {
            $counter++;
            return $inner;
        });

        $container->get('thing');
        $container->get('thing');

        $this->assertSame(2, $counter);
    }

    public function testExtendOnSingletonCachesDecoratedResult(): void
    {
        $container = $this->getContainer();
        $container->set('thing', Bread::class);
        $container->extend('thing', fn(Bread $inner) => new Wrapper('only', $inner));

        $this->assertSame($container->get('thing'), $container->get('thing'));
    }
}
