<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Inject
{
    public function __construct(public readonly string $id)
    {
    }
}
