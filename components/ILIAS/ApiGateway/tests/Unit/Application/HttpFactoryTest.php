<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use ILIAS\ApiGateway\Application\ErrorHandler;
use ILIAS\ApiGateway\Application\HttpServiceFactory;
use ILIAS\ApiGateway\Application\RouteDispatcher;
use ILIAS\ApiGateway\Contracts\WebConfig;
use ILIAS\ApiGateway\Contracts\Webservice;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;

final class HttpFactoryTest extends TestCase
{
    private HttpServiceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new HttpServiceFactory();
    }

    public function testCreatesInstanceOfRouteDispatcher(): void
    {
        $webservice = $this->createMock(Webservice::class);
        $expected = new RouteDispatcher($webservice);

        $actual = $this->factory->createRouteDispatcher($webservice);

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfErrorHandler(): void
    {
        $webservice = $this->createMock(Webservice::class);
        $webconfig = $this->createMock(WebConfig::class);
        $logger = $this->createMock(LoggerInterface::class);
        $responseFactory = $this->createMock(ResponseFactory::class);

        $expected = new ErrorHandler(
            $webservice,
            $webconfig,
            $logger,
            $responseFactory,
        );

        $actual = $this->factory->createErrorHandler(
            $webservice,
            $webconfig,
            $logger,
            $responseFactory,
        );

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfWebAppApplication(): void
    {
        $actual = $this->factory->createWebApplication();

        self::assertInstanceOf(SlimApp::class, $actual);
    }
}
