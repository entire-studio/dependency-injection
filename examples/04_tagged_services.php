<?php

/**
 * Tagged services — group multiple bindings under a label, then resolve them
 * as an array in registration order. Useful for plugins, event listeners,
 * middleware stacks, validators, etc.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

interface EventListener
{
    public function handle(string $event): void;
}

class AuditListener implements EventListener
{
    public function handle(string $event): void
    {
        echo "  audit: $event" . PHP_EOL;
    }
}

class MetricsListener implements EventListener
{
    public function handle(string $event): void
    {
        echo "  metrics: $event" . PHP_EOL;
    }
}

class EventBus
{
    /** @param list<EventListener> $listeners */
    public function __construct(private readonly array $listeners) {}

    public function dispatch(string $event): void
    {
        echo "dispatch: $event" . PHP_EOL;
        foreach ($this->listeners as $listener) {
            $listener->handle($event);
        }
    }
}

$di = new Container();
$di->tag(AuditListener::class, 'event.listener');
$di->tag(MetricsListener::class, 'event.listener');

$di->set(EventBus::class, fn(Container $c) => new EventBus(
    /** @var list<EventListener> $listeners */
    listeners: $c->getTagged('event.listener'),
));

$di->get(EventBus::class)->dispatch('user.signed_up');

// Detach with untag(); cleared on unset()/clear() too.
$di->untag(MetricsListener::class, 'event.listener');
echo 'remaining: ' . count($di->getTagged('event.listener')) . PHP_EOL; // 1
