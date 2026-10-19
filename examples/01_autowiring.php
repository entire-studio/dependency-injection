<?php

/**
 * Autowiring — the container reflects on constructor signatures and resolves
 * dependencies recursively. Interfaces and abstract classes need a binding;
 * concrete classes are built automatically.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

interface Logger
{
    public function log(string $message): void;
}

class StdoutLogger implements Logger
{
    public function log(string $message): void
    {
        echo '[log] ' . $message . PHP_EOL;
    }
}

class UserRepository
{
    public function __construct(public readonly Logger $logger) {}

    public function find(int $id): string
    {
        $this->logger->log("loading user #$id");
        return "user#$id";
    }
}

class UserService
{
    public function __construct(public readonly UserRepository $repo) {}
}

$di = new Container();
$di->set(Logger::class, StdoutLogger::class);

$service = $di->get(UserService::class);
echo $service->repo->find(42) . PHP_EOL;
