<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Buffet
{
    /** @var array<int, string> */
    public readonly array $names;

    public function __construct(
        public readonly Bread $bread,
        string ...$names,
    ) {
        $this->names = $names;
    }
}
