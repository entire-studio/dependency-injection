<?php

/**
 * #[Inject(id)] — override autowiring for a single constructor parameter.
 *
 * Two main uses:
 *   1. Disambiguate when multiple implementations exist and you don't want
 *      to (or can't) re-bind the interface globally.
 *   2. Inject scalar values from named bindings (DSN, API keys, feature flags)
 *      that the type system can't resolve on its own.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Attributes\Inject;
use EntireStudio\DependencyInjection\Container;

interface Queue
{
    public function name(): string;
}

class RedisQueue implements Queue
{
    public function name(): string
    {
        return 'redis';
    }
}

class SqsQueue implements Queue
{
    public function name(): string
    {
        return 'sqs';
    }
}

class Worker
{
    public function __construct(
        // Scalar from a named binding
        #[Inject('worker.concurrency')] public readonly int $concurrency,
        // Pick a specific Queue impl without aliasing the interface
        #[Inject('queue.high_priority')] public readonly Queue $highPriority,
        #[Inject('queue.low_priority')] public readonly Queue $lowPriority,
    ) {}
}

$di = new Container();
$di->value('worker.concurrency', 8);
$di->set('queue.high_priority', RedisQueue::class);
$di->set('queue.low_priority', SqsQueue::class);

$worker = $di->get(Worker::class);
echo "concurrency: {$worker->concurrency}" . PHP_EOL;
echo "high: {$worker->highPriority->name()}, low: {$worker->lowPriority->name()}" . PHP_EOL;
