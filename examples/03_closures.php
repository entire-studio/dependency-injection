<?php

/**
 * Closure bindings — when autowiring isn't enough (primitive args, runtime
 * config, conditional wiring), pass a Closure. It receives the container so
 * you can compose dependencies manually.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EntireStudio\DependencyInjection\Container;

class HttpClient
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly int $timeoutSeconds,
    ) {}
}

class WeatherApi
{
    public function __construct(public readonly HttpClient $http) {}
}

$di = new Container();

// Closure resolves primitives from config
$di->value('http.base_url', 'https://api.example.com');
$di->set(HttpClient::class, fn(Container $c) => new HttpClient(
    baseUrl: $c->get('http.base_url'),
    timeoutSeconds: 30,
));

// WeatherApi is still autowired — its only dep is HttpClient
$api = $di->get(WeatherApi::class);
echo $api->http->baseUrl . PHP_EOL; // https://api.example.com

// Closure bindings cache as singletons (same as set with a class-string).
// To get fresh instances, use factory() instead:
$di->factory('request.id', fn() => bin2hex(random_bytes(4)));
echo $di->get('request.id') . PHP_EOL;
echo $di->get('request.id') . PHP_EOL; // different
