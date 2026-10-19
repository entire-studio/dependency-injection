<?php

/**
 * injectMethod() — invoke a setter on the resolved instance with autowired
 * arguments. Useful for optional dependencies, framework-style "configure
 * after construction" flows, or breaking circular constructor deps.
 *
 * Multiple injectMethod() calls on the same id stack in registration order.
 * Override individual args by name via the third parameter.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

interface Cache
{
    public function get(string $key): ?string;
}

class NullCache implements Cache
{
    public function get(string $key): ?string
    {
        return null;
    }
}

class ReportGenerator
{
    public ?Cache $cache = null;
    public string $label = 'unnamed';

    public function setCache(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}

$di = new Container();
$di->set(Cache::class, NullCache::class);

// setCache: Cache is autowired; setLabel: 'label' is overridden by name
$di->injectMethod(ReportGenerator::class, 'setCache');
$di->injectMethod(ReportGenerator::class, 'setLabel', ['label' => 'quarterly']);

$report = $di->get(ReportGenerator::class);
echo $report->label . PHP_EOL;                  // quarterly
echo $report->cache !== null ? 'cache set' . PHP_EOL : 'no cache' . PHP_EOL;
