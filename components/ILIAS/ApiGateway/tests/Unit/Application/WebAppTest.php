<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use ILIAS\ApiGateway\Application\Bootstrap\ErrorHandlerSetup;
use ILIAS\ApiGateway\Application\Bootstrap\MiddlewarePipeline;
use ILIAS\ApiGateway\Application\Bootstrap\RouteBootstrapper;
use ILIAS\ApiGateway\Application\WebApp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;

#[CoversClass(WebApp::class)]
final class WebAppTest extends TestCase
{
    /** @var MockObject&SlimApp<\Psr\Container\ContainerInterface> */
    private MockObject&SlimApp $slimApp;
    private MockObject&MiddlewarePipeline $middlewarePipeline;
    private MockObject&RouteBootstrapper $routeBootstrapper;
    private MockObject&ErrorHandlerSetup $errorHandlerSetup;
    private WebApp $webApp;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->slimApp = $this->createMock(SlimApp::class);
        $this->middlewarePipeline = $this->createMock(MiddlewarePipeline::class);
        $this->routeBootstrapper = $this->createMock(RouteBootstrapper::class);
        $this->errorHandlerSetup = $this->createMock(ErrorHandlerSetup::class);

        $this->webApp = new WebApp(
            $this->slimApp,
            $this->middlewarePipeline,
            $this->routeBootstrapper,
            $this->errorHandlerSetup
        );
    }

    public function testRunsEntireApiProcess(): void
    {
        $this->middlewarePipeline->expects($this->once())
            ->method('apply')
            ->with($this->slimApp, $this->errorHandlerSetup);

        $this->routeBootstrapper->expects($this->once())
            ->method('register')
            ->with($this->slimApp);

        $this->slimApp->expects($this->once())
            ->method('run')
            ->with(null);

        $this->webApp->run();
    }

    public function testRunsWithRequest(): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);

        $this->slimApp->expects($this->once())
            ->method('run')
            ->with($requestMock);

        $this->webApp->run($requestMock);
    }
}
