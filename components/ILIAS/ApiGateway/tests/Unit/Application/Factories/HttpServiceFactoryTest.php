<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Factories\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;

final class HttpServiceFactoryTest extends TestCase
{
    private HttpServiceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new HttpServiceFactory();
    }

    public function testCreatesInstanceOfResponseHandler(): void
    {
        $webservice = $this->createMock(Webservice::class);
        $expected = new ResponseHandler($webservice);

        $actual = $this->factory->createResponseHandler($webservice);

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfErrorHandler(): void
    {
        $webservice = $this->createMock(Webservice::class);
        $monitoring = new ServiceMonitoring(true, true, true);
        $logger = $this->createMock(LoggerInterface::class);
        $responseFactory = $this->createMock(ResponseFactory::class);

        $expected = new ErrorHandler(
            $webservice,
            $monitoring,
            $logger,
            $responseFactory,
        );

        $actual = $this->factory->createErrorHandler(
            $webservice,
            $monitoring,
            $logger,
            $responseFactory,
        );

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfWebAppApplication(): void
    {
        $actual = $this->factory->createWebApplication();

        self::assertInstanceOf(SlimApp::class, $actual); // @phpstan-ignore-line
    }
}
