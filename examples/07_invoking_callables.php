<?php

/**
 * call() — invoke any callable with autowired arguments. Accepts every PHP
 * callable form: Closure, [$obj, 'method'], [Class::class, 'static'],
 * 'Class::static', invokable objects. Pass overrides keyed by parameter name.
 *
 * Great for action/handler dispatch where the framework wires deps and the
 * caller supplies the request payload.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

class Database
{
    public function insert(string $table, array $row): int
    {
        echo "INSERT INTO $table " . json_encode($row) . PHP_EOL;
        return 99;
    }
}

class CreateUserHandler
{
    public function __invoke(Database $db, string $email): int
    {
        return $db->insert('users', ['email' => $email]);
    }
}

$di = new Container();

// Closure with mixed autowired + named args
$id = $di->call(
    fn(Database $db, string $email) => $db->insert('users', ['email' => $email]),
    ['email' => 'alice@example.com'],
);
echo "created #$id" . PHP_EOL;

// Invokable handler — container resolves $db, caller supplies $email
$id = $di->call($di->get(CreateUserHandler::class), ['email' => 'bob@example.com']);
echo "created #$id" . PHP_EOL;

// Static method via string
class Math
{
    public static function double(int $n): int
    {
        return $n * 2;
    }
}
echo $di->call(Math::class . '::double', ['n' => 21]) . PHP_EOL; // 42
