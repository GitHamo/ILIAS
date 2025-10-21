<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use PHPUnit\Framework\TestCase;
use LogicException;
use InvalidArgumentException;
use ReflectionProperty;

final class RoutesRegistryTest extends TestCase
{
    /** @var RoutesRegistry */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();
        // The RoutesRegistry is a singleton. To ensure a clean state for each test,
        // we need to reset the singleton instance and its properties using reflection.
        $this->resetSingleton(RoutesRegistry::getInstance());
        $this->registry = RoutesRegistry::getInstance();
    }

    private function resetSingleton(RoutesRegistry $registry): void
    {
        $instance_property = new ReflectionProperty(RoutesRegistry::class, 'instance');
        $instance_property->setAccessible(true);
        $instance_property->setValue(null, null);

        $routes_property = new ReflectionProperty($registry, 'routes');
        $routes_property->setAccessible(true);
        $routes_property->setValue($registry, []);
    }

    public function test_it_is_a_singleton(): void
    {
        $first_instance = RoutesRegistry::getInstance();
        $second_instance = RoutesRegistry::getInstance();

        self::assertSame($first_instance, $second_instance);
    }

    public function test_it_throws_exception_on_unserialize(): void
    {
        self::expectException(LogicException::class);
        self::expectExceptionMessage('Cannot unserialize a singleton.');

        $serialized = serialize(RoutesRegistry::getInstance());
        unserialize($serialized);
    }

    public function test_it_registers_and_retrieves_a_route(): void
    {
        $path = '/test';
        $method = 'GET';
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn([$method]);

        $this->registry->register($route);

        self::assertTrue($this->registry->has($method, $path));
        self::assertSame($route, $this->registry->get($method, $path));
    }

    public function test_it_registers_route_with_multiple_methods(): void
    {
        $path = '/test';
        $methods = ['GET', 'POST', 'PUT'];
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn($methods);

        $this->registry->register($route);

        foreach ($methods as $method) {
            self::assertTrue($this->registry->has($method, $path));
            self::assertSame($route, $this->registry->get($method, $path));
        }
    }

    public function test_it_handles_http_methods_case_insensitively(): void
    {
        $path = '/test';
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn(['get', 'Post']);

        $this->registry->register($route);

        self::assertTrue($this->registry->has('GET', $path));
        self::assertSame($route, $this->registry->get('gEt', $path));
        self::assertTrue($this->registry->has('POST', $path));
        self::assertSame($route, $this->registry->get('pOsT', $path));
    }

    public function test_it_throws_exception_for_route_with_no_methods(): void
    {
        $path = '/no-methods';
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot register a route with no HTTP methods for path '{$path}'.");

        $this->registry->register($route);
    }

    public function test_it_throws_exception_for_duplicate_route(): void
    {
        $path = '/duplicate';
        $method = 'GET';
        $route = $this->createMock(Route::class);

        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn([$method]);

        $this->registry->register($route);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Duplicate route detected: Cannot re-register 'GET {$path}'.");

        $this->registry->register($route);
    }

    public function test_get_returns_null_for_non_existent_path(): void
    {
        self::assertNull($this->registry->get('GET', '/non-existent'));
    }

    public function test_get_returns_null_for_non_existent_method(): void
    {
        $path = '/exists';
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn($path);
        $route->method('getMethods')->willReturn(['GET']);
        $this->registry->register($route);

        self::assertNull($this->registry->get('POST', $path));
    }

    public function test_has_returns_false_for_non_existent_route(): void
    {
        self::assertFalse($this->registry->has('GET', '/non-existent'));
    }

    public function test_all_returns_empty_array_initially(): void
    {
        self::assertEmpty($this->registry->all());
    }

    public function test_all_returns_all_registered_routes(): void
    {
        $route1 = $this->createMock(Route::class);

        $route1->method('getPath')->willReturn('/route1');
        $route1->method('getMethods')->willReturn(['GET']);

        $route2 = $this->createMock(Route::class);

        $route2->method('getPath')->willReturn('/route2');
        $route2->method('getMethods')->willReturn(['POST', 'PUT']);

        $this->registry->register($route1);
        $this->registry->register($route2);

        $expected = [
            'GET /route1' => $route1,
            'POST /route2' => $route2,
            'PUT /route2' => $route2,
        ];

        self::assertEquals($expected, $this->registry->all());
    }
}
