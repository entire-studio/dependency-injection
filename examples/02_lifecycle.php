<?php

/**
 * Three binding styles control lifecycle:
 *   set()     — singleton (default). First get() builds, later calls reuse.
 *   factory() — per-call. Every get() builds a fresh instance.
 *   value()   — literal. Stores any scalar, array, or pre-built object.
 *
 * Re-registering an id invalidates its cached instance.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

class Clock
{
    public readonly float $bornAt;

    public function __construct()
    {
        $this->bornAt = microtime(true);
    }
}

$di = new Container();

// singleton — same instance both times
$di->set('shared.clock', Clock::class);
var_dump($di->get('shared.clock') === $di->get('shared.clock')); // true

// factory — fresh each call
$di->factory('fresh.clock', fn() => new Clock());
var_dump($di->get('fresh.clock') === $di->get('fresh.clock')); // false

// value — literal pass-through
$di->value('db.dsn', 'sqlite::memory:');
$di->value('app.flags', ['beta' => true, 'tracing' => false]);
echo $di->get('db.dsn') . PHP_EOL;
var_dump($di->get('app.flags'));

// unset() drops a single binding; clear() resets everything user-bound
$di->unset('shared.clock');
var_dump($di->has('shared.clock')); // false

$di->clear();
var_dump($di->has('db.dsn')); // false
