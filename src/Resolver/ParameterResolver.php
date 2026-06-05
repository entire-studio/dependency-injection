<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Resolver;

use EntireStudio\DependencyInjection\Attributes\Inject;
use EntireStudio\DependencyInjection\Container;
use EntireStudio\DependencyInjection\Exceptions\ContainerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final class ParameterResolver
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(ReflectionParameter $param, string $id): mixed
    {
        $name = $param->getName();

        $injectAttrs = $param->getAttributes(Inject::class);
        if ($injectAttrs !== []) {
            $injectId = $injectAttrs[0]->newInstance()->id;
            try {
                return $this->container->get($injectId);
            } catch (NotFoundExceptionInterface $e) {
                throw new ContainerException(
                    sprintf(
                        'Failed to resolve class "%s" because #[Inject("%s")] target for param "%s" was not found.',
                        $id,
                        $injectId,
                        $name
                    ),
                    0,
                    $e
                );
            }
        }

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
                return $this->container->get($typeName);
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
