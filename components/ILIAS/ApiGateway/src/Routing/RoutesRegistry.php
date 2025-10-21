<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Routing;

use ILIAS\ApiGateway\Routing\Route;
use InvalidArgumentException;
use LogicException;

class RoutesRegistry
{
    private static ?self $instance = null;

    /** @var array<string, \ILIAS\ApiGateway\Routing\Route> */
    private array $routes = [];

    private function __construct() {}

    private function __clone(): void {}

    public function __wakeup(): void
    {
        throw new LogicException("Cannot unserialize a singleton.");
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Registers a route for all of its specified HTTP methods.
     * Throws an exception if any method/path combination is already registered.
     */
    public function register(Route $route): void
    {
        $path = $route->getPath();
        $methods = $route->getMethods();

        if (empty($methods)) {
            throw new InvalidArgumentException("Cannot register a route with no HTTP methods for path '{$path}'.");
        }

        foreach ($methods as $method) {
            $key = $this->getInternalKey($method, $path);

            if (isset($this->routes[$key])) {
                throw new LogicException("Duplicate route detected: Cannot re-register '{$key}'.");
            }

            $this->routes[$key] = $route;
        }
    }

    public function get(string $method, string $path): ?Route
    {
        $key = $this->getInternalKey($method, $path);

        return $this->routes[$key] ?? null;
    }

    /**
     * @return array<string, \ILIAS\ApiGateway\Routing\Route>
     */
    public function all(): array
    {
        return $this->routes;
    }

    public function has(string $method, string $path): bool
    {
        $key = $this->getInternalKey($method, $path);

        return isset($this->routes[$key]);
    }

    private function getInternalKey(string $method, string $path): string
    {
        return strtoupper($method) . ' ' . $path;
    }
}
