<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

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

    private function __construct()
    {
    }

    /**
     * @codeCoverageIgnore because any test would fail before this line is executed
     */
    private function __clone()
    {
    }

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
