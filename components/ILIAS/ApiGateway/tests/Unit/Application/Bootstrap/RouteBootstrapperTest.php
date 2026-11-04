<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bootstrap;

use Closure;
use ILIAS\ApiGateway\Application\Factories\MiddlewareFactory;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Application\Bootstrap\RouteBootstrapper;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Routing\HttpMethod;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\App as SlimApp;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteGroupInterface;
use Slim\Routing\RouteCollectorProxy;

#[CoversClass(RouteBootstrapper::class)]
final class RouteBootstrapperTest extends TestCase
{
    private MockObject&RoutesRegistry $registry;
    private MockObject&MiddlewareFactory $middlewareFactory;
    private MockObject&ResponseHandler $responseHandler;
    private ServiceIdentity $identity;
    /** @var MockObject&SlimApp<\Psr\Container\ContainerInterface> */
    private MockObject&SlimApp $slimApp;
    private RouteBootstrapper $bootstrapper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(RoutesRegistry::class);
        $this->middlewareFactory = $this->createMock(MiddlewareFactory::class);
        $this->responseHandler = $this->createMock(ResponseHandler::class);
        $this->identity = new ServiceIdentity(ServiceProtocol::REST, 'https://localhost', ServiceProtocol::REST->value);
        $this->slimApp = $this->createMock(SlimApp::class);

        $this->bootstrapper = new RouteBootstrapper(
            $this->registry,
            $this->middlewareFactory,
            $this->responseHandler,
            $this->identity
        );
    }

    #[DataProvider('protocolProvider')]
    public function testRegistersRoutesWithCorrectGroup(ServiceProtocol $protocol, string $expectedGroupPath): void
    {
        $identity = new ServiceIdentity($protocol, 'https://localhost', $protocol->value);
        $this->registry->method('all')->willReturn([]);

        $this->slimApp->expects($this->once())
            ->method('group')
            ->with($expectedGroupPath, $this->isInstanceOf(Closure::class))
            ->willReturn($this->createMock(RouteGroupInterface::class));

        $bootstrapper = new RouteBootstrapper(
            $this->registry,
            $this->middlewareFactory,
            $this->responseHandler,
            $identity
        );

        $bootstrapper->register($this->slimApp);
    }

    /**
     * @return array<string, array{ServiceProtocol, string}>
     */
    public static function protocolProvider(): array
    {
        return [
            'REST' => [ServiceProtocol::REST, '/rest'],
            'SOAP' => [ServiceProtocol::SOAP, '/soap'],
            'GraphQL' => [ServiceProtocol::GraphQL, '/graphql'],
            'gRPC' => [ServiceProtocol::gRPC, '/grpc'],
        ];
    }

    public function testRegistersRoutesWithCorrectHandlersAndMiddlewares(): void
    {
        $action = $this->createMock(Action::class);
        $slimRoute = $this->createMock(RouteInterface::class);
        $middlewareMock = $this->createMock(MiddlewareInterface::class);

        $this->registry->expects($this->once())
            ->method('all')
            ->willReturn([
                $this->createConfiguredMock(Route::class, [
                    'getMethod' => HttpMethod::GET,
                    'getPath' => '/foo',
                    'getAction' => $action,
                    'getMiddlewares' => ['SomeMiddleware'],
                ]),
            ]);

        $this->middlewareFactory->method('create')->with('SomeMiddleware')->willReturn($middlewareMock);

        $capturedHandler = null; // capture the closure passed to Slim's map()

        $this->slimApp->expects($this->once())
            ->method('group')
            ->with('/rest', $this->isInstanceOf(Closure::class))
            ->willReturnCallback(function (string $path, Closure $callback) use (&$capturedHandler, $slimRoute) {
                $group = $this->createMock(RouteCollectorProxy::class);

                // verify both routes are mapped with correct methods and paths
                $group->expects($this->once())
                    ->method('map')
                    ->with(['GET'], '/foo', $this->isInstanceOf(Closure::class))
                    ->willReturnCallback(function (array $methods, string $pattern, Closure $handler) use (&$capturedHandler, $slimRoute) {
                        $capturedHandler = $handler;
                        return $slimRoute;
                    });

                $callback($group); // execute the group callback to trigger map()
                return $this->createMock(RouteGroupInterface::class);
            });

        // verify route-specific middleware is added to the route
        $slimRoute->expects($this->once())->method('add')->with($middlewareMock);

        $this->bootstrapper->register($this->slimApp);

        $this->assertNotNull($capturedHandler);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $args = ['id' => '1'];

        // assert that ResponseHandler is invoked correctly when the closure runs
        $this->responseHandler->expects($this->once())
            ->method('__invoke')
            ->with($request, $response, $args, $action)
            ->willReturn($response);

        $result = $capturedHandler($request, $response, $args);
        $this->assertSame($response, $result);
    }
}
