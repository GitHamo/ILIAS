<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

// Define two distinct interfaces to ensure mocks have unique class names
interface MiddlewareA extends MiddlewareInterface {}
interface MiddlewareB extends MiddlewareInterface {}

#[CoversClass(MiddlewareRepository::class)]
class MiddlewareRepositoryTest extends TestCase
{
    public function testGetReturnsMiddlewareByClassName(): void
    {
        $middleware1 = $this->createMock(MiddlewareA::class);
        $middleware2 = $this->createMock(MiddlewareB::class);

        $middlewares = [$middleware1, $middleware2];
        $repository = new MiddlewareRepository($middlewares);

        $actual = $repository->get(\get_class($middleware1));

        self::assertSame($middleware1, $actual);
    }

    public function testGetThrowsExceptionWhenMiddlewareIsNotFound(): void
    {
        $middleware1 = $this->createMock(MiddlewareA::class);
        $middleware2 = $this->createMock(MiddlewareB::class);

        $middlewares = [$middleware1];
        $repository = new MiddlewareRepository($middlewares);

        self::expectException(LogicException::class);

        $repository->get(\get_class($middleware2));
    }

    public function testGetThrowsExceptionWhenMiddlewareIsNotInstanceOfMiddlewareInterface(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = new \stdClass();

        $middlewares = [$middleware1];
        $repository = new MiddlewareRepository($middlewares);

        self::expectException(LogicException::class);

        $repository->get(\get_class($middleware2));
    }

    public function testGetAllHandlesMultipleMiddlewareInstancesOfTheSameClassKeepingTheLastOne(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);

        $middlewares = [$middleware1, $middleware2];
        $repository = new MiddlewareRepository($middlewares);

        $actual = $repository->get(\get_class($middleware1));

        self::assertSame($middleware2, $actual); // The last instance ($middleware2) should overwrite the first
    }
}
