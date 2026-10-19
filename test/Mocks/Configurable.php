<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Test\Mocks;

class Configurable
{
    /** @var list<string> */
    public array $log = [];

    public ?Bread $bread = null;

    public function setBread(Bread $bread): void
    {
        $this->bread = $bread;
        $this->log[] = 'bread';
    }

    public function setLabel(string $label = 'default'): void
    {
        $this->log[] = 'label:' . $label;
    }
}
