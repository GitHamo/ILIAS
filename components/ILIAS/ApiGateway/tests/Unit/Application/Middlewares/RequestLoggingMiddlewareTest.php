<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use DomainException;
use Exception;
use ILIAS\ApiGateway\Application\Middlewares\RequestLoggingMiddleware;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\Data\UUID\Factory as UuidFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception as RamseyUuidException;

#[CoversClass(RequestLoggingMiddleware::class)]
class RequestLoggingMiddlewareTest extends TestCase
{
    private const string CORRELATION_HEADER = 'X-Correlation-ID';
    private const string CORRELATION_UUID = '123e4567-e89b-12d3-a456-426655440000';
    private MockObject&LoggerInterface $loggerMock;
    private MockObject&UuidFactory $uuidFactoryMock;
    private MockObject&ServerRequestInterface $requestMock;
    private MockObject&RequestHandlerInterface $requestHandlerMock;
    private MockObject&ResponseInterface $responseMock;
    private MockObject&UriInterface $uriMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestMock = $this->createConfiguredMock(ServerRequestInterface::class, [
            'getUri' => $this->uriMock = $this->createMock(UriInterface::class),
        ]);
        $this->requestHandlerMock = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $this->responseMock = $this->createMock(ResponseInterface::class),
        ]);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->uuidFactoryMock = $this->createMock(UuidFactory::class);
        $this->requestMock->method('withAttribute')->willReturn($this->requestMock);
        $this->responseMock->method('withHeader')->willReturn($this->responseMock);
    }

    public function testLogsRequestDetailsWhenLoggingIsEnabled(): void
    {
        $monitoring = new ServiceMonitoring(true, true, true);
        $middleware = new RequestLoggingMiddleware($monitoring, $this->loggerMock, $this->uuidFactoryMock);

        $method = 'GET';
        $statusCode = 200;
        $logContext = [
            'method' => $method,
            'path' => $path = '/api/test',
            'status_code' => $statusCode,
            'correlation_id' => self::CORRELATION_UUID,
        ];

        $this->requestHandlerMock->expects(static::once())
            ->method('handle')
            ->with(self::identicalTo($this->requestMock));

        $this->requestMock->expects(static::once())
            ->method('getHeaderLine')
            ->with(self::CORRELATION_HEADER)
            ->willReturn(self::CORRELATION_UUID);

        $this->requestMock->method('getMethod')->willReturn($method);
        $this->responseMock->method('getStatusCode')->willReturn($statusCode);
        $this->uriMock->expects(static::never())->method('__toString');
        $this->uriMock->expects(static::once())->method('getPath')->willReturn($path);

        $this->uuidFactoryMock->expects(static::once())->method('fromString')->with(self::CORRELATION_UUID);
        $this->uuidFactoryMock->expects(static::never())->method('uuid4AsString');

        $this->loggerMock->expects(static::once())
            ->method('info')
            ->with(
                self::matchesRegularExpression('/^\[GET\] 200 \/api\/test in \d+\.\d+ms$/'),
                self::callback(
                    fn(array $actual): bool =>
                    // 1. Check that the keys we care about match exactly
                    array_intersect_key($actual, $logContext) === $logContext
                        // 2. Just verify the variable key exists and is a number
                        && \is_float($actual['execution_time_ms'] ?? null)
                        // 3. Do not retain query-bearing request URIs in log context
                        && !\array_key_exists('uri', $actual)
                ),
            );

        $actual = $middleware->process($this->requestMock, $this->requestHandlerMock);

        self::assertSame($this->responseMock, $actual);
    }

    public function testLogsRequestWithoutOptionalDetails(): void
    {
        $monitoring = new ServiceMonitoring(true, true, true);
        $middleware = new RequestLoggingMiddleware($monitoring, $this->loggerMock, $this->uuidFactoryMock);

        $logContext = [
            'method' => '',
            'path' => '',
            'status_code' => 0,
        ];

        $this->requestMock->method('getServerParams')->willReturn([]);
        $this->requestMock->method('getAttribute')->with('user')->willReturn(null);

        $this->loggerMock->expects(static::once())
            ->method('info')
            ->with(
                self::anything(),
                self::callback(
                    fn(array $actual): bool =>
                    array_intersect_key($actual, $logContext) === $logContext
                ),
            );

        $middleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testReturnsResponseWhenLoggingIsDisabled(): void
    {
        $monitoring = new ServiceMonitoring(true, false, true);
        $middleware = new RequestLoggingMiddleware($monitoring, $this->loggerMock, $this->uuidFactoryMock);

        $this->requestMock->expects(static::once())
            ->method('getHeaderLine')
            ->with(self::CORRELATION_HEADER)
            ->willReturn(self::CORRELATION_UUID);

        // Still propagate correlation ID on response
        $this->responseMock->expects(static::once())
            ->method('withHeader')
            ->with(self::CORRELATION_HEADER, self::CORRELATION_UUID);

        $this->loggerMock->expects(static::never())->method('info');
        $this->requestMock->expects(static::never())->method('getServerParams');
        $this->requestMock->expects(static::never())->method('getMethod');
        $this->requestMock->expects(static::never())->method('getUri');
        $this->responseMock->expects(static::never())->method('getStatusCode');
        $this->uriMock->expects(static::never())->method('getPath');

        $middleware->process($this->requestMock, $this->requestHandlerMock);
    }

    public function testGeneratesNewCorrelationId(): void
    {
        $monitoring = new ServiceMonitoring(true, true, true);
        $middleware = new RequestLoggingMiddleware($monitoring, $this->loggerMock, $this->uuidFactoryMock);

        $this->requestMock->expects(static::once())
            ->method('getHeaderLine')
            ->with(self::CORRELATION_HEADER)
            ->willReturn('');

        $this->uuidFactoryMock->expects(static::once())
            ->method('uuid4AsString')
            ->willReturn(self::CORRELATION_UUID);

        $this->uuidFactoryMock->expects(static::never())->method('fromString');

        $middleware->process($this->requestMock, $this->requestHandlerMock);
    }

    #[DataProvider('invalidCorrelationIdProvider')]
    public function testThrowsExceptionForInvalidCorrelationId(
        Exception $raised,
        Exception $exepected,
    ): void {
        $monitoring = new ServiceMonitoring(true, true, true);
        $middleware = new RequestLoggingMiddleware($monitoring, $this->loggerMock, $this->uuidFactoryMock);

        $this->requestMock->expects(static::once())
            ->method('getHeaderLine')
            ->with(self::CORRELATION_HEADER)
            ->willReturn('invald-uuid');

        $this->uuidFactoryMock->expects(static::once())->method('fromString')->willThrowException($raised);

        self::expectExceptionObject($exepected);

        $middleware->process($this->requestMock, $this->requestHandlerMock);
    }

    /**
     * @return array<string, Exception[]>
     */
    public static function invalidCorrelationIdProvider(): array
    {
        return [
            'invalid-argument-exception' => [
                $argumentException = new RamseyUuidException\InvalidArgumentException(),
                new InvalidArgumentException(
                    "The correlation id must be a valid UUID.",
                    400,
                    $argumentException,
                ),
            ],
            'invalid-uuid-exception' => [
                $uuidException = new RamseyUuidException\InvalidUuidStringException(),
                new InvalidArgumentException(
                    "The correlation id must be a valid UUID.",
                    400,
                    $uuidException,
                ),
            ],
            'normal-exception' => [
                $normalException = new DomainException('foo', 300),
                $normalException,
            ],
        ];
    }
}
