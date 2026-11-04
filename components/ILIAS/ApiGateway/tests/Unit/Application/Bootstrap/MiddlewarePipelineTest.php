<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bootstrap;

use ILIAS\ApiGateway\Application\Bootstrap\ErrorHandlerSetup;
use ILIAS\ApiGateway\Application\Factories\MiddlewareFactory;
use ILIAS\ApiGateway\Application\Bootstrap\MiddlewarePipeline;
use ILIAS\ApiGateway\Application\Middlewares;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Slim\App as SlimApp;

#[CoversClass(MiddlewarePipeline::class)]
final class MiddlewarePipelineTest extends TestCase
{
    private const int DEFAULT_MIDDLEWARES_TOTAL = 6;

    private MockObject&Webservice $webservice;
    private MockObject&MiddlewareFactory $middlewareFactory;
    private MockObject&MiddlewareInterface $middlewareMock;
    /** @var MockObject&SlimApp<\Psr\Container\ContainerInterface> */
    private MockObject&SlimApp $slimApp;
    private MockObject&ErrorHandlerSetup $errorHandlerSetup;
    private MiddlewarePipeline $pipeline;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->webservice = $this->createMock(Webservice::class);
        $this->middlewareFactory = $this->createMock(MiddlewareFactory::class);
        $this->middlewareMock = $this->createMock(MiddlewareInterface::class);
        $this->slimApp = $this->createMock(SlimApp::class);
        $this->errorHandlerSetup = $this->createMock(ErrorHandlerSetup::class);

        $this->pipeline = new MiddlewarePipeline(
            $this->webservice,
            $this->middlewareFactory,
        );
    }

    public function testRegistersMiddlewaresInCorrectOrder(): void
    {
        $wsMiddlewares = ['WS1', 'WS2'];
        $this->webservice->method('getMiddlewares')->willReturn($wsMiddlewares);

        $this->middlewareFactory->method('create')->willReturn($this->middlewareMock);

        // Sequence of 'add' calls: WS1, WS2, Logging, RateLimit, Status, CORS, Browser, Transport
        $totalAddCalls = \count($wsMiddlewares) + self::DEFAULT_MIDDLEWARES_TOTAL;

        $this->slimApp->expects($this->exactly($totalAddCalls))
            ->method('add')
            ->with($this->middlewareMock);

        $this->slimApp->expects($this->once())->method('addRoutingMiddleware');

        $this->errorHandlerSetup->expects($this->once())
            ->method('register')
            ->with($this->slimApp);

        $this->pipeline->apply($this->slimApp, $this->errorHandlerSetup);
    }
}
