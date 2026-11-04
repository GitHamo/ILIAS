<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routes;

use ILIAS\ApiGateway\Application\Routes\PingRoute;
use ILIAS\ApiGateway\Routing\HttpMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PingRoute::class)]
final class PingRouteTest extends TestCase
{
    private PingRoute $route;

    #[\Override]
    protected function setUp(): void
    {
        $this->route = new PingRoute();
    }

    public function testCreatesRouteWithResults(): void
    {
        /** @var array<string, mixed> */
        $actual = $this->route->getAction()([], null);

        self::assertSame('Ping', $this->route->getName());
        self::assertSame('/ping', $this->route->getPath());
        self::assertSame(HttpMethod::GET, $this->route->getMethod());
        self::assertArrayHasKey('pong', $actual);
        self::assertTrue($actual['pong']);
        self::assertCount(1, $actual); // Ensure only 'pong' is present
    }

    public function testCreatesRouteIgnoresParameters(): void
    {
        /** @var array<string, mixed> */
        $actual = $this->route->getAction()(['foo' => 'bar'], null);

        self::assertArrayHasKey('pong', $actual);
        self::assertTrue($actual['pong']);
        self::assertCount(1, $actual); // Ensure only 'pong' is present
        self::assertArrayNotHasKey('foo', $actual);
    }
}
