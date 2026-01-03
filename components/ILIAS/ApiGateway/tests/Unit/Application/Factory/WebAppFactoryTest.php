<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factory;

use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Application\ErrorHandler;
use ILIAS\ApiGateway\Application\Factory\HttpConfigFactory;
use ILIAS\ApiGateway\Application\Factory\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Factory\WebAppFactory;
use ILIAS\ApiGateway\Application\ResponseHandler;
use ILIAS\ApiGateway\Application\WebApp;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Logging\WebserviceLoggerFactory;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;

final class WebAppFactoryTest extends TestCase
{
    private WebAppFactory $factory;
    private HttpConfigFactory&MockObject $httpConfigFactory;
    private HttpServiceFactory&MockObject $httpServiceFactory;
    private WebserviceFactory&MockObject $webserviceFactory;
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

        $this->httpConfigFactory = $this->createMock(HttpConfigFactory::class);
        $this->httpServiceFactory = $this->createMock(HttpServiceFactory::class);
        $this->webserviceFactory = $this->createMock(WebserviceFactory::class);
        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->routesRegistry = $this->createMock(RoutesRegistry::class);
        $this->middlewareRepository = $this->createMock(MiddlewareRepository::class);
        $this->activityRoutesAutoloader = $this->createMock(ActivityRoutesAutoloader::class);
        $this->routesAutoloader = $this->createMock(RoutesAutoloader::class);
        $this->loggerFactory = $this->createMock(WebserviceLoggerFactory::class);

        $this->factory = new WebAppFactory(
            $this->httpConfigFactory,
            $this->httpServiceFactory,
            $this->webserviceFactory,
            $this->responseFactory,
            $this->routesRegistry,
            $this->middlewareRepository,
            $this->activityRoutesAutoloader,
            $this->routesAutoloader,
            $this->loggerFactory
        );
    }

    public function testUsesComponentsToCreateInstanceOfWebApp(): void
    {
        $protocol = ServiceProtocol::SOAP;
        $webConfigMock = $this->createMock(WebConfig::class);
        $webserviceMock = $this->createMock(Webservice::class);
        $responseHandlerMock = $this->createMock(ResponseHandler::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorHandlerMock = $this->createMock(ErrorHandler::class);
        /** @var SlimApp<\Psr\Container\ContainerInterface>&MockObject */
        $applicationMock = $this->createMock(SlimApp::class);

        $this->httpConfigFactory->expects(self::once())
            ->method('createWebConfig')
            ->with(self::identicalTo($protocol))
            ->willReturn($webConfigMock);

        $this->webserviceFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($webConfigMock))
            ->willReturn($webserviceMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createResponseHandler')
            ->with(self::identicalTo($webserviceMock))
            ->willReturn($responseHandlerMock);

        $this->loggerFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($protocol->value))
            ->willReturn($loggerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createErrorHandler')
            ->with(
                self::identicalTo($webserviceMock),
                self::identicalTo($webConfigMock),
                self::identicalTo($loggerMock),
                self::identicalTo($this->responseFactory),
            )
            ->willReturn($errorHandlerMock);

        $this->httpServiceFactory->expects(self::once())
            ->method('createWebApplication')
            ->willReturn($applicationMock);

        $expected = new WebApp(
            $webConfigMock,
            $this->routesRegistry,
            $this->middlewareRepository,
            $responseHandlerMock,
            $errorHandlerMock,
            $loggerMock,
            $this->responseFactory,
            $applicationMock,
        );

        $actual = $this->factory->create($protocol);

        self::assertEquals($expected, $actual);
    }
}
