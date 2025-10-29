<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use ILIAS\ApiGateway\Application\ErrorHandler;
use ILIAS\ApiGateway\Application\RouteDispatcher;
use ILIAS\ApiGateway\Application\WebApp;
use ILIAS\ApiGateway\Configuration\WebConfig;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RouteHandler;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Routing\RouteCollectorProxy;

class WebAppTest extends TestCase
{
    private MockObject&RoutesRegistry $registry;
    private MockObject&RouteDispatcher $dispatcher;
    private MockObject&ErrorHandler $errorHandler;
    private MockObject&LoggerInterface $logger;
    /** @var MockObject&SlimApp<\Psr\Container\ContainerInterface> */
    private MockObject&SlimApp $app;
    private MockObject&ResponseFactory $responseFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(RoutesRegistry::class);
        $this->dispatcher = $this->createMock(RouteDispatcher::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->app = $this->createMock(SlimApp::class);
        $this->responseFactory = $this->createMock(ResponseFactory::class);
    }

    /**
     * Business Case: If the API is turned off by an admin, it should stop processing any calls
     * and just show a 'Service Unavailable' message for maintenance.
     */
    public function testExitIfWebserviceIsDisabled(): void
    {
        $webApp = $this->createWebApp(isEnabled: false);

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamHandle = fopen('php://memory', 'r+');
        
        if ($streamHandle === false) {
            self::fail('Failed to open memory stream for testing.');
        }
        
        $responseBodyMock = new \Slim\Psr7\Stream($streamHandle);

        $this->responseFactory->method('createResponse')
            ->with(503, 'Service Unavailable')
            ->willReturn($responseMock);

        $responseMock->method('withHeader')
            ->with('Content-Type', 'text/plain')
            ->willReturn($responseMock);

        $responseMock->method('getBody')->willReturn($responseBodyMock);

        $this->app->expects(self::never())->method('run');

        ob_start();
        $webApp->run();
        ob_end_clean();

        $responseBodyMock->rewind();

        self::assertEquals('API Service is currently disabled.', $responseBodyMock->getContents());
    }

    /**
     * Business Case: When a request matches a defined API endpoint, the system should hand it off
     * to the central request dispatcher to be processed.
     */
    public function testDispatchToTheCorrectHandlerWhenRouteIsMatched(): void
    {
        $webApp = $this->createWebApp(
            isEnabled: true,
            basePath: '/foo',
        );

        $routeHandler = $this->createMock(RouteHandler::class);
        $routeMock = $this->createMock(Route::class);

        $routeMock->method('getMethods')->willReturn(['GET']);
        $routeMock->method('getPath')->willReturn('/test');
        $routeMock->method('getHandler')->willReturn($routeHandler);

        $this->registry->expects(self::once())
            ->method('all')
            ->willReturn([$routeMock]);

        $groupProxyMock = $this->createMock(RouteCollectorProxy::class);

        $this->app->method('group')
            ->willReturnCallback(function (string $pattern, callable $callable) use ($groupProxyMock) {
                $callable($groupProxyMock);

                return $this->createMock(\Slim\Interfaces\RouteGroupInterface::class);
            })
        ;

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $args = ['id' => '42'];

        $this->dispatcher->expects(self::once())
            ->method('__invoke')
            ->with($request, $response, $args, $routeHandler);

        // Inside the callback, we immediately execute the handler to verify it triggers the dispatcher expectation above.
        $groupProxyMock->expects(self::once())
            ->method('map')
            ->with(
                ['GET'],
                '/test',
                $this->callback(function (callable $handler) use ($request, $response, $args) {
                    $handler($request, $response, $args);
                    return true; // Callback constraint must return true.
                })
            );

        $webApp->run();
    }

    /**
     * Business Case: Once the API is set up and ready, it needs to start running so it can
     * begin listening for and handling incoming requests.
     */
    public function testRunAppIfWebserviceIsEnabled(): void
    {
        $webApp = $this->createWebApp();

        $this->app->expects(self::once())->method('addRoutingMiddleware');
        $this->app->expects(self::once())->method('run');

        $webApp->run();
    }

    /**
     * Business Case: To help with debugging, error handling should be configured based on the environment.
     * This ensures the right level of error detail is logged or displayed.
     */
    public function testRegisterErrorHandler(): void
    {
        $webApp = $this->createWebApp(
            debugMode: $debugMode = true,
            logErrors: $logErrors = false,
            logErrorDetails: $logErrorDetails = true,
        );

        $this->app->expects(self::once())
            ->method('addErrorMiddleware')
            ->with(
                self::identicalTo($debugMode),
                self::identicalTo($logErrors),
                self::identicalTo($logErrorDetails),
                self::identicalTo($this->logger),
            )
            ->willReturn(
                $errorMiddlewareMock = $this->createMock(ErrorMiddleware::class)
            )
        ;

        $errorMiddlewareMock->expects(self::once())
            ->method('setDefaultErrorHandler')
            ->with(
                self::identicalTo($this->errorHandler),
            );

        $webApp->run();
    }

    private function createWebApp(
        string $baseUrl = '',
        string $basePath = '',
        bool $isEnabled = true,
        bool $debugMode = false,
        bool $logErrors = false,
        bool $logErrorDetails = false,
    ): WebApp {
        return new WebApp(
            new WebConfig(
                $baseUrl,
                $basePath,
                $isEnabled,
                $debugMode,
                $logErrors,
                $logErrorDetails,
            ),
            $this->registry,
            $this->dispatcher,
            $this->errorHandler,
            $this->logger,
            $this->app,
            $this->responseFactory,
        );
    }
}
