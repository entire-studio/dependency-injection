<?php

/**
 * extend() — stack decorators on a binding. Each decorator receives the
 * previously-resolved instance and returns a wrapper. Stacks in registration
 * order; the outermost layer is what get() returns.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

interface Mailer
{
    public function send(string $to, string $body): void;
}

class SmtpMailer implements Mailer
{
    public function send(string $to, string $body): void
    {
        echo "  smtp -> $to: $body" . PHP_EOL;
    }
}

class LoggingMailer implements Mailer
{
    public function __construct(private readonly Mailer $inner) {}

    public function send(string $to, string $body): void
    {
        echo "[log] sending to $to" . PHP_EOL;
        $this->inner->send($to, $body);
    }
}

class RetryingMailer implements Mailer
{
    public function __construct(private readonly Mailer $inner, private readonly int $attempts) {}

    public function send(string $to, string $body): void
    {
        echo "[retry up to {$this->attempts}x]" . PHP_EOL;
        $this->inner->send($to, $body);
    }
}

$di = new Container();
$di->set(Mailer::class, SmtpMailer::class);
$di->extend(Mailer::class, fn(Mailer $inner) => new LoggingMailer($inner));
$di->extend(Mailer::class, fn(Mailer $inner) => new RetryingMailer($inner, 3));

// Composition order: SmtpMailer -> LoggingMailer -> RetryingMailer (outermost)
$di->get(Mailer::class)->send('alice@example.com', 'hello');
