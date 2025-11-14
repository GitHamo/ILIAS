<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use ArrayIterator;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RouteStaticRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RoutesAutoloaderTest extends TestCase
{
    private RoutesAutoloader $autoloader;
    private RoutesRegistry&MockObject $routesRegistry;
    private RouteStaticRepository&MockObject $routeRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->routesRegistry = $this->createMock(RoutesRegistry::class);
        $this->routeRepository = $this->createMock(RouteStaticRepository::class);

        $this->autoloader = new RoutesAutoloader($this->routesRegistry, $this->routeRepository);
    }

    public function testRegistersRoute(): void
    {
        $this->routeRepository->expects(self::once())
            ->method('getAll')
            ->willReturn(new ArrayIterator([
                $this->createMock(Route::class),
                $this->createMock(Route::class),
            ]));
        $this->routesRegistry->expects(self::exactly(2))
            ->method('register')
            ->with(self::isInstanceOf(Route::class));

        $this->autoloader->load();
    }

    public function testEmptyRoutes(): void
    {
        $this->routeRepository->expects(self::once())
            ->method('getAll')
            ->willReturn(new ArrayIterator([]));

        $this->routesRegistry->expects(self::never())->method('register');

        $this->autoloader->load();
    }
}
