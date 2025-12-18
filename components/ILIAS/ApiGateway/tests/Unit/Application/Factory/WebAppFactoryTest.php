<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factory;

use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Application\ErrorHandler;
use ILIAS\ApiGateway\Application\Factory\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Factory\WebAppFactory;
use ILIAS\ApiGateway\Application\RouteExecutor;
use ILIAS\ApiGateway\Application\WebApp;
use ILIAS\ApiGateway\Contracts\ServiceProtocol;
use ILIAS\ApiGateway\Contracts\WebConfig;
use ILIAS\ApiGateway\Contracts\Webservice;
use ILIAS\ApiGateway\Logging\WebserviceLoggerFactory;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;

final class WebAppFactoryTest extends TestCase
{
    private WebAppFactory $factory;
    private WebserviceFactory&MockObject $webserviceFactory;
    private HttpServiceFactory&MockObject $httpServiceFactory;
    private ResponseFactory&MockObject $responseFactory;
    private RoutesRegistry&MockObject $routesRegistry;
    private MiddlewareRepository&MockObject $middlewareRepository;
    private ActivityRoutesAutoloader&MockObject $activityRoutesAutoloader;
    private RoutesAutoloader&MockObject $routesAutoloader;
    private WebserviceLoggerFactory&MockObject $loggerFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->webserviceFactory = $this->createMock(WebserviceFactory::class);
        $this->httpServiceFactory = $this->createMock(HttpServiceFactory::class);
        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->routesRegistry = $this->createMock(RoutesRegistry::class);
        $this->middlewareRepository = $this->createMock(MiddlewareRepository::class);
        $this->activityRoutesAutoloader = $this->createMock(ActivityRoutesAutoloader::class);
        $this->routesAutoloader = $this->createMock(RoutesAutoloader::class);
        $this->loggerFactory = $this->createMock(WebserviceLoggerFactory::class);

        $this->factory = new WebAppFactory(
            $this->webserviceFactory,
            $this->httpServiceFactory,
            $this->responseFactory,
            $this->routesRegistry,
            $this->middlewareRepository,
            $this->activityRoutesAutoloader,
            $this->routesAutoloader,
            $this->loggerFactory
        );
    }

    public function testusesComponentsToCreateInstanceOfWebApp(): void
    {
        $config = new WebConfig(
            'foo',
            ServiceProtocol::SOAP,
            true,
            true,
            true,
            true,
        );

        $webserviceMock = $this->createMock(Webservice::class);
        $executorMock = $this->createMock(RouteExecutor::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorHandlerMock = $this->createMock(ErrorHandler::class);
        /** @var SlimApp<\Psr\Container\ContainerInterface>&MockObject */
        $applicationMock = $this->createMock(SlimApp::class);

        $this->webserviceFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($config))
            ->willReturn($webserviceMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createRouteExecutor')
            ->with(self::identicalTo($webserviceMock))
            ->willReturn($executorMock);

        $this->loggerFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($config->getProtocol()->value))
            ->willReturn($loggerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createErrorHandler')
            ->with(
                self::identicalTo($webserviceMock),
                self::identicalTo($config),
                self::identicalTo($loggerMock),
                self::identicalTo($this->responseFactory),
            )
            ->willReturn($errorHandlerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createWebApplication')
            ->willReturn($applicationMock);

        $expected = new WebApp(
            $config,
            $this->routesRegistry,
            $this->middlewareRepository,
            $executorMock,
            $errorHandlerMock,
            $loggerMock,
            $this->responseFactory,
            $applicationMock,
        );

        $actual = $this->factory->create($config);

        self::assertEquals($expected, $actual);
    }
}
