<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Greeter
{
    public function greet(Bread $bread, string $who = 'world'): string
    {
        return sprintf('hello %s with %s', $who, $bread::class);
    }

    public static function staticGreet(Bread $bread): string
    {
        return 'static hello ' . $bread::class;
    }

    public function __invoke(Bread $bread): string
    {
        return 'invoked with ' . $bread::class;
    }
}
