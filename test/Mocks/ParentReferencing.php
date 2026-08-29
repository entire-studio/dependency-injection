<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class ParentReferencing extends ParentReferencingBase
{
    public function __construct(
        private readonly parent $other,
    ) {
    }
}
