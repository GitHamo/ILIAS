<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use Exception;
use ILIAS\ApiGateway\Application\ErrorHandler;
use ILIAS\ApiGateway\Configuration\WebConfig;
use ILIAS\ApiGateway\Models\Payload;
use ILIAS\ApiGateway\Webservice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $handler;
    private string $payloadBody = 'Specific Message';
    private MockObject&Webservice $webservice;
    private MockObject&LoggerInterface $logger;
    private MockObject&ResponseFactoryInterface $responseFactory;
    private MockObject&ServerRequestInterface $request;
    private MockObject&ResponseInterface $response;
    private MockObject&StreamInterface $stream;

    #[\Override]
    protected function setUp(): void
    {
        $this->webservice = $this->createMock(Webservice::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);

        $this->responseFactory->method('createResponse')->willReturn($this->response);
        $this->response->method('getBody')->willReturn($this->stream);
        $this->webservice->method('handleError')->willReturn(
            new Payload(
                body: $this->payloadBody,
            ),
        );

        $this->handler = $this->createErrorHandler();
    }

    public function testReturns500IfExceptionCodeIsNotAValidHttpErrorCode(): void
    {
        $exception = new Exception('Test Exception', 0);

        $this->responseFactory->expects(self::once())
            ->method('createResponse')
            ->with(500)
            ->willReturn($this->response);

        $response = ($this->handler)($this->request, $exception);

        self::assertSame($this->response, $response);
    }

    public function testUsesExceptionCodeAsStatusInCaseOfA4xxError(): void
    {
        $exception = new Exception('Not Found', 404);

        $this->responseFactory->expects(self::once())
            ->method('createResponse')
            ->with(404)
            ->willReturn($this->response);

        $response = ($this->handler)($this->request, $exception);

        self::assertSame($this->response, $response);
    }

    public function testUsesExceptionCodeAsStatusInCaseOfA5xxError(): void
    {
        $exception = new Exception('Service Unavailable', 503);

        $this->responseFactory->expects(self::once())
            ->method('createResponse')
            ->with(503)
            ->willReturn($this->response);

        $response = ($this->handler)($this->request, $exception);

        self::assertSame($this->response, $response);
    }

    public function testWritesPayloadFromWebserviceToResponseBody(): void
    {
        $exception = new Exception('Any Exception', 200);

        $this->stream->expects(self::once())
            ->method('write')
            ->with($this->payloadBody);

        ($this->handler)($this->request, $exception);
    }

    public function testDoesNotLogIfLoggingIsDisabled(): void
    {
        $exception = new Exception('Any Exception');

        $this->logger->expects(static::never())->method('error');

        ($this->handler)($this->request, $exception);
    }

    public function testLogsOnlyMessageIfDetailsAreDisabled(): void
    {
        $exception = new Exception($message = 'A specific error occurred');

        $handler = $this->createErrorHandler(
            logErrors: true,
        );

        $this->logger->expects(self::once())
            ->method('error')
            ->with($message);

        $handler($this->request, $exception);
    }

    public function testLogsMessageAndStacktraceIfDetailsAreEnabled(): void
    {
        $exception = new Exception($message = 'A specific error occurred');

        $handler = $this->createErrorHandler(
            logErrors: true,
            logErrorDetails: true,
        );

        $expectedLog = "{$message}\nStack trace:\n" . (string)$exception;

        $this->logger->expects(self::once())
            ->method('error')
            ->with($expectedLog);

        $handler($this->request, $exception);
    }

    private function createErrorHandler(
        bool $debugMode = false,
        bool $logErrors = false,
        bool $logErrorDetails = false,
    ): ErrorHandler {
        return new ErrorHandler(
            $this->webservice,
            new WebConfig(
                'http://localhost',
                '/api',
                true,
                $debugMode,
                $logErrors,
                $logErrorDetails,
            ),
            $this->logger,
            $this->responseFactory,
        );
    }
}
