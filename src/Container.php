<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection;

use Closure;
use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use EntireStudio\DependencyInjection\Exceptions\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class Container implements ContainerInterface
{
    /** @var array<string, Closure|string> */
    private array $entries = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $factories = [];

    /** @var array<string, true> */
    private array $resolving = [];

    public function __construct()
    {
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        $originalId = $id;
        $seen = [];

        while ($this->has($id)) {
            if (isset($seen[$id])) {
                throw new ContainerException(
                    sprintf(
                        'Alias cycle detected: %s.',
                        implode(' -> ', [...array_keys($seen), $id])
                    )
                );
            }

            $seen[$id] = true;
            $entry = $this->entries[$id];

            if ($entry instanceof Closure) {
                $instance = $entry($this);
                return $this->cache($originalId, $instance);
            }

            $id = $entry;
        }

        return $this->cache($originalId, $this->resolve($id));
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }

    public function set(string $id, Closure|string $concrete): void
    {
        $this->entries[$id] = $concrete;
        unset($this->instances[$id], $this->factories[$id]);
    }

    public function factory(string $id, Closure $factory): void
    {
        $this->entries[$id] = $factory;
        $this->factories[$id] = true;
        unset($this->instances[$id]);
    }

    private function cache(string $id, mixed $instance): mixed
    {
        if (!isset($this->factories[$id])) {
            $this->instances[$id] = $instance;
        }
        return $instance;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    private function resolve(string $id): object
    {
        if (!class_exists($id) && !interface_exists($id)) {
            throw new NotFoundException(
                sprintf(
                    'Class "%s" does not exist.',
                    $id,
                )
            );
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException(
                sprintf(
                    'Circular dependency detected while resolving "%s" (chain: %s).',
                    $id,
                    implode(' -> ', [...array_keys($this->resolving), $id])
                )
            );
        }

        $reflectionClass = new ReflectionClass($id);

        if ($reflectionClass->isInterface()) {
            throw new ContainerException(
                sprintf(
                    'No binding registered for interface "%s".',
                    $id
                )
            );
        }

        if ($reflectionClass->isAbstract()) {
            throw new ContainerException(
                sprintf(
                    'No binding registered for abstract class "%s".',
                    $id
                )
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

        $this->resolving[$id] = true;

        try {
            $dependencies = [];
            foreach ($parameters as $param) {
                if ($param->isVariadic()) {
                    continue;
                }
                $dependencies[] = $this->resolveParameter($param, $id);
            }
        } finally {
            unset($this->resolving[$id]);
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private function resolveParameter(ReflectionParameter $param, string $id): mixed
    {
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

        if ($type instanceof ReflectionIntersectionType) {
            throw new ContainerException(
                sprintf(
                    'Failed to resolve class "%s" because of intersection type for param "%s".',
                    $id,
                    $name
                )
            );
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            return $this->get($type->getName());
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new ContainerException(
            sprintf(
                'Failed to resolve class "%s" because param "%s" has built-in type "%s" and no default value.',
                $id,
                $name,
                $type instanceof ReflectionNamedType ? $type->getName() : (string) $type
            )
        );
    }
}
