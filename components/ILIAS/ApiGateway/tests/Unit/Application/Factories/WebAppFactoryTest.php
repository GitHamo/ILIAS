<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Bootstrap;
use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ILIAS\ApiGateway\Application\Factories\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Factories\MiddlewareFactory;
use ILIAS\ApiGateway\Application\Factories\RoutesRegistryFactory;
use ILIAS\ApiGateway\Application\Factories\WebAppFactory;
use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Application\WebApp;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\HTTP\Response\ResponseFactory;
use ILIAS\Logging\Logger\LoggerFactoryInterface;
use ILIAS\Logging\Logger\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\App as SlimApp;

final class WebAppFactoryTest extends TestCase
{
    private WebAppFactory $factory;
    private HttpConfigFactory&MockObject $httpConfigFactory;
    private HttpServiceFactory&MockObject $httpServiceFactory;
    private WebserviceFactory&MockObject $webserviceFactory;
    private RoutesRegistryFactory&MockObject $routesRegistryFactory;
    private MiddlewareRepository&MockObject $middlewareRepository;
    private ResponseFactory&MockObject $responseFactory;
    private LoggerFactoryInterface&MockObject $loggerFactory;
    private RateLimiting&MockObject $rateLimitingService;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->httpConfigFactory = $this->createMock(HttpConfigFactory::class);
        $this->httpServiceFactory = $this->createMock(HttpServiceFactory::class);
        $this->webserviceFactory = $this->createMock(WebserviceFactory::class);
        $this->routesRegistryFactory = $this->createMock(RoutesRegistryFactory::class);
        $this->middlewareRepository = $this->createMock(MiddlewareRepository::class);
        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->loggerFactory = $this->createMock(LoggerFactoryInterface::class);
        $this->rateLimitingService = $this->createMock(RateLimiting::class);

        $this->factory = new WebAppFactory(
            $this->httpConfigFactory,
            $this->httpServiceFactory,
            $this->webserviceFactory,
            $this->routesRegistryFactory,
            $this->middlewareRepository,
            $this->responseFactory,
            $this->loggerFactory,
            $this->rateLimitingService,
        );
    }

    public function testUsesComponentsToCreateInstanceOfWebApp(): void
    {
        $protocol = ServiceProtocol::SOAP;

        $identity = new ServiceIdentity($protocol, 'http://localhost', $protocol->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $monitoring = new ServiceMonitoring(true, true, true);
        $webConfig = new WebConfig($identity, $access, $monitoring);

        $authConfigMock = $this->createMock(AuthConfig::class);
        $webserviceMock = $this->createMock(Webservice::class);
        $routesRegistryMock = $this->createMock(RoutesRegistry::class);
        $responseHandlerMock = $this->createMock(ResponseHandler::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorHandlerMock = $this->createMock(ErrorHandler::class);
        /** @var SlimApp<\Psr\Container\ContainerInterface>&MockObject */
        $applicationMock = $this->createMock(SlimApp::class);

        $this->httpConfigFactory->expects(self::once())
            ->method('createWebConfig')
            ->with(self::identicalTo($protocol))
            ->willReturn($webConfig);

        $this->httpConfigFactory->expects(self::once())
            ->method('createAuthConfig')
            ->willReturn($authConfigMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createResponseHandler')
            ->with(self::identicalTo($webserviceMock))
            ->willReturn($responseHandlerMock);

        $this->routesRegistryFactory->expects(self::once())
            ->method('create')
            ->willReturn($routesRegistryMock);

        $this->webserviceFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($webConfig))
            ->willReturn($webserviceMock);

        $this->loggerFactory->expects(self::once())
            ->method('getLazy')
            ->with(self::identicalTo('webservices'))
            ->willReturn($loggerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createErrorHandler')
            ->with(
                self::identicalTo($webserviceMock),
                self::identicalTo($monitoring),
                self::identicalTo($loggerMock),
                self::identicalTo($this->responseFactory),
            )
            ->willReturn($errorHandlerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createWebApplication')
            ->willReturn($applicationMock);

        $middlewareFactory = new MiddlewareFactory(
            $webConfig,
            $authConfigMock,
            $this->middlewareRepository,
            $this->responseFactory,
            $this->rateLimitingService,
            $loggerMock,
        );

        $expected = new WebApp(
            $applicationMock,
            new Bootstrap\MiddlewarePipeline($webserviceMock, $middlewareFactory),
            new Bootstrap\RouteBootstrapper($routesRegistryMock, $middlewareFactory, $responseHandlerMock, $identity),
            new Bootstrap\ErrorHandlerSetup($monitoring, $errorHandlerMock, $loggerMock),
        );

        $actual = $this->factory->create($protocol);

        self::assertEquals($expected, $actual);
    }
}
