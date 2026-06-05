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
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

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

    /** @var array<string, ReflectionClass<object>> */
    private array $reflectionCache = [];

    /** @var array<string, list<array{method: string, args: array<string, mixed>}>> */
    private array $methodInjections = [];

    /** @var array<string, list<string>> */
    private array $tags = [];

    /** @var array<string, list<Closure>> */
    private array $decorators = [];

    public function __construct()
    {
        $this->registerSelf();
    }

    private function registerSelf(): void
    {
        $this->instances[ContainerInterface::class] = $this;
        $this->instances[self::class] = $this;
        $this->instances[static::class] = $this;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function get(string $id): mixed
    {
        $id = $this->normalize($id);

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
                try {
                    $instance = $entry($this);
                } catch (ContainerExceptionInterface | NotFoundExceptionInterface $e) {
                    throw $e;
                } catch (Throwable $e) {
                    throw new ContainerException(
                        sprintf(
                            'Closure binding for "%s" threw %s: %s',
                            $originalId,
                            $e::class,
                            $e->getMessage()
                        ),
                        0,
                        $e
                    );
                }
                return $this->cache($originalId, $instance);
            }

            $id = $entry;
        }

        return $this->cache($originalId, $this->resolve($id));
    }

    public function has(string $id): bool
    {
        $id = $this->normalize($id);

        return isset($this->entries[$id]) || array_key_exists($id, $this->instances);
    }

    /**
     * Register a binding. The concrete may be:
     *  - a class-string to alias to (also followed transitively via further set() calls), or
     *  - a Closure called with the container, whose return value is cached as a singleton.
     *
     * Re-registering clears any previously cached instance for this id.
     */
    public function set(string $id, Closure|string $concrete): void
    {
        $id = $this->normalize($id);
        $concrete = is_string($concrete) ? $this->normalize($concrete) : $concrete;

        $this->entries[$id] = $concrete;
        unset($this->instances[$id], $this->factories[$id]);
    }

    /**
     * Register a factory binding. Unlike set() with a Closure, the result of each
     * get($id) is NOT cached — every call invokes the factory and returns a new instance.
     */
    public function factory(string $id, Closure $factory): void
    {
        $id = $this->normalize($id);

        $this->entries[$id] = $factory;
        $this->factories[$id] = true;
        unset($this->instances[$id]);
    }

    /**
     * Register a literal value (scalar, array, object instance). get($id) returns
     * it as-is without resolution or invocation. Useful for configuration values
     * required by autowired constructors.
     */
    public function value(string $id, mixed $value): void
    {
        $id = $this->normalize($id);

        $this->instances[$id] = $value;
        unset($this->entries[$id], $this->factories[$id]);
    }

    /**
     * Remove the entry, any cached instance, and any factory marker for $id.
     * Subsequent get($id) will attempt fresh resolution (autowiring) instead.
     */
    public function unset(string $id): void
    {
        $id = $this->normalize($id);

        unset(
            $this->entries[$id],
            $this->instances[$id],
            $this->factories[$id],
            $this->reflectionCache[$id],
            $this->methodInjections[$id],
            $this->decorators[$id],
        );

        foreach ($this->tags as $tag => $taggedIds) {
            $this->tags[$tag] = array_values(array_filter(
                $taggedIds,
                fn(string $taggedId): bool => $taggedId !== $id,
            ));
        }
    }

    /**
     * Drop all entries, cached instances, factories, and resolution state.
     * The container's self-registration (ContainerInterface, self) is preserved.
     */
    public function clear(): void
    {
        $this->entries = [];
        $this->instances = [];
        $this->factories = [];
        $this->resolving = [];
        $this->reflectionCache = [];
        $this->methodInjections = [];
        $this->tags = [];
        $this->decorators = [];
        $this->registerSelf();
    }

    /**
     * Wrap an already-registered binding. The decorator receives the previously
     * resolved instance and the container, and returns the replacement instance.
     * Multiple extend() calls stack in registration order — the last registered
     * decorator is the outermost wrapper.
     *
     * @throws ContainerException when no entry has been registered for $id
     */
    public function extend(string $id, Closure $decorator): void
    {
        $id = $this->normalize($id);

        if (!isset($this->entries[$id])) {
            throw new ContainerException(
                sprintf('Cannot extend "%s": no binding registered.', $id)
            );
        }

        $this->decorators[$id][] = $decorator;
        unset($this->instances[$id]);
    }

    /**
     * Attach a tag to an entry. Multiple tags per id allowed; duplicate
     * (id, tag) pairs are ignored.
     */
    public function tag(string $id, string $tag): void
    {
        $id = $this->normalize($id);
        if (!in_array($id, $this->tags[$tag] ?? [], true)) {
            $this->tags[$tag][] = $id;
        }
    }

    /**
     * Detach a tag from an entry. No-op if the pair isn't tagged.
     */
    public function untag(string $id, string $tag): void
    {
        $id = $this->normalize($id);
        if (!isset($this->tags[$tag])) {
            return;
        }
        $this->tags[$tag] = array_values(array_filter(
            $this->tags[$tag],
            fn(string $taggedId): bool => $taggedId !== $id,
        ));
    }

    /**
     * Resolve every id with this tag, in registration order.
     *
     * @return list<mixed>
     */
    public function getTagged(string $tag): array
    {
        return array_map(
            fn(string $id): mixed => $this->get($id),
            $this->tags[$tag] ?? [],
        );
    }

    /**
     * Register a method to be invoked on the instance after construction,
     * with arguments autowired (and overridable via $args by parameter name).
     * Multiple injectMethod() calls for the same id stack in registration order.
     *
     * @param array<string, mixed> $args
     */
    public function injectMethod(string $id, string $method, array $args = []): void
    {
        $id = $this->normalize($id);
        $this->methodInjections[$id][] = ['method' => $method, 'args' => $args];
    }

    /**
     * Invoke any callable with autowired arguments. Pass overrides keyed by
     * parameter name in $args; remaining parameters are resolved via the same
     * rules as constructor autowiring. Variadic parameters are skipped.
     *
     * Accepts every PHP callable form: Closure, [object, 'method'],
     * [class-string, 'method'] (static), 'Class::method', invokable objects.
     *
     * @param array<string, mixed> $args
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function call(callable $callable, array $args = []): mixed
    {
        $closure = Closure::fromCallable($callable);
        $reflection = new ReflectionFunction($closure);
        $context = $reflection->getName();

        $resolved = [];
        foreach ($reflection->getParameters() as $param) {
            if ($param->isVariadic()) {
                continue;
            }
            $name = $param->getName();
            if (array_key_exists($name, $args)) {
                $resolved[] = $args[$name];
                continue;
            }
            $resolved[] = $this->resolveParameter($param, $context);
        }

        return $closure(...$resolved);
    }

    private function cache(string $id, mixed $instance): mixed
    {
        $instance = $this->applyDecorators($id, $instance);
        $this->applyMethodInjections($id, $instance);

        if (!isset($this->factories[$id])) {
            $this->instances[$id] = $instance;
        }
        return $instance;
    }

    private function applyDecorators(string $id, mixed $instance): mixed
    {
        foreach ($this->decorators[$id] ?? [] as $decorator) {
            $instance = $decorator($instance, $this);
        }
        return $instance;
    }

    private function applyMethodInjections(string $id, mixed $instance): void
    {
        if (!is_object($instance) || !isset($this->methodInjections[$id])) {
            return;
        }

        foreach ($this->methodInjections[$id] as $injection) {
            $callable = [$instance, $injection['method']];
            if (!is_callable($callable)) {
                throw new ContainerException(
                    sprintf(
                        'Method injection for "%s" failed: method "%s" is not callable on %s.',
                        $id,
                        $injection['method'],
                        $instance::class
                    )
                );
            }
            $this->call($callable, $injection['args']);
        }
    }

    private function normalize(string $id): string
    {
        return ltrim($id, '\\');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolve(string $id): object
    {
        if (!class_exists($id) && !interface_exists($id) && !trait_exists($id)) {
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

        /** @var class-string $id */
        $reflectionClass = $this->reflectionCache[$id] ??= new ReflectionClass($id);

        if ($reflectionClass->isInterface()) {
            throw new ContainerException(
                sprintf(
                    'No binding registered for interface "%s".',
                    $id
                )
            );
        }

        if ($reflectionClass->isEnum()) {
            throw new ContainerException(
                sprintf(
                    'Cannot autowire enum "%s" — register a binding.',
                    $id
                )
            );
        }

        if ($reflectionClass->isTrait()) {
            throw new ContainerException(
                sprintf(
                    'Cannot autowire trait "%s" — traits cannot be instantiated.',
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

        if (!$constructor || !$constructor->getParameters()) {
            try {
                return $reflectionClass->newInstance();
            } catch (ReflectionException $e) {
                throw new ContainerException(
                    sprintf('Failed to instantiate "%s": %s', $id, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        $this->resolving[$id] = true;

        try {
            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                if ($param->isVariadic()) {
                    continue;
                }
                $dependencies[] = $this->resolveParameter($param, $id);
            }
        } finally {
            unset($this->resolving[$id]);
        }

        try {
            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ContainerException(
                sprintf('Failed to instantiate "%s": %s', $id, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            throw new ContainerException(
                sprintf(
                    'Failed to resolve class "%s" because of union type for param "%s".',
                    $id,
                    $name
                )
            );
        }

        if ($type instanceof ReflectionIntersectionType) {
            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            throw new ContainerException(
                sprintf(
                    'Failed to resolve class "%s" because of intersection type for param "%s".',
                    $id,
                    $name
                )
            );
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $typeName = $type->getName();

            if (in_array($typeName, ['self', 'parent', 'static'], true)) {
                throw new ContainerException(
                    sprintf(
                        'Cannot autowire "%s" type hint for param "%s" of class "%s" — register a callable binding.',
                        $typeName,
                        $name,
                        $id
                    )
                );
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }
            try {
                return $this->get($typeName);
            } catch (NotFoundExceptionInterface $e) {
                throw new ContainerException(
                    sprintf(
                        'Failed to resolve class "%s" because dependency "%s" for param "%s" was not found.',
                        $id,
                        $typeName,
                        $name
                    ),
                    0,
                    $e
                );
            }
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
