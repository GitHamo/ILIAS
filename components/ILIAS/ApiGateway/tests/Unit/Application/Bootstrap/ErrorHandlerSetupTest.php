<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Bootstrap;

use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Application\Bootstrap\ErrorHandlerSetup;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;
use Slim\Middleware\ErrorMiddleware;

#[CoversClass(ErrorHandlerSetup::class)]
final class ErrorHandlerSetupTest extends TestCase
{
    private ServiceMonitoring $monitoring;
    private MockObject&ErrorHandler $errorHandler;
    private MockObject&LoggerInterface $logger;
    /** @var MockObject&SlimApp<\Psr\Container\ContainerInterface> */
    private MockObject&SlimApp $slimApp;
    private ErrorHandlerSetup $setup;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->monitoring = new ServiceMonitoring(
            isDebugEnabled: true,
            isLoggingEnabled: true,
            isLoggingDetailsEnabled: false
        );
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->slimApp = $this->createMock(SlimApp::class);

        $this->setup = new ErrorHandlerSetup(
            $this->monitoring,
            $this->errorHandler,
            $this->logger,
        );
    }

    public function testRegistersErrorHandlerWithCorrectSettings(): void
    {
        $errorMiddleware = $this->createMock(ErrorMiddleware::class);

        $this->slimApp->expects($this->once())
            ->method('addErrorMiddleware')
            ->with(
                $this->monitoring->isDebugEnabled,
                $this->monitoring->isLoggingEnabled,
                $this->monitoring->isLoggingDetailsEnabled,
                $this->logger
            )
            ->willReturn($errorMiddleware);

        $errorMiddleware->expects($this->once())
            ->method('setDefaultErrorHandler')
            ->with($this->errorHandler);

        $this->setup->register($this->slimApp);
    }
}
