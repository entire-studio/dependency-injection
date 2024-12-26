<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection;

use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Container implements ContainerInterface
{
    private static ?Container $instance = null;
    /** @var array<string, callable|string> */
    private array $entries = [];

    public function __construct()
    {
    }

    /**
     * @throws ContainerException|NotFoundException|ReflectionException
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
            $entry = $this->entries[$id];

            if (is_callable($entry)) {
                return $entry($this);
            }

            $id = $entry;
        }

        return $this->resolve($id);
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    public function set(string $id, callable|string $concrete): void
    {
        $this->entries[$id] = $concrete;
    }

    /**
     * @throws NotFoundException|ContainerException|ReflectionException
     */
    private function resolve(string $id): object
    {
        try {
            $reflectionClass = new ReflectionClass($id);
        } catch (ReflectionException $e) {
            throw new NotFoundException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException(
                sprintf(
                    'Class "%s" is not instantiable.',
                    $id
                )
            );
        }

        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return new $id();
        }

        $parameters = $constructor->getParameters();

        if (!$parameters) {
            return new $id();
        }

        $dependencies = array_map(
            /**
             * @throws ContainerException|NotFoundException|ReflectionException
             */
            function (ReflectionParameter $param) use ($id) {
                $name = $param->getName();
                $type = $param->getType();

                if (!$type) {
                    throw new ContainerException(
                        sprintf(
                            'Failed to resolve class "%s" because param "%s" is missing a type hint.',
                            $id,
                            $name
                        )
                    );
                }

                if ($type instanceof ReflectionUnionType) {
                    throw new ContainerException(
                        sprintf(
                            'Failed to resolve class "%s" because of union type for param "%s".',
                            $id,
                            $name
                        )
                    );
                }

                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    return $this->get($type->getName());
                }
            },
            $parameters
        );

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}
