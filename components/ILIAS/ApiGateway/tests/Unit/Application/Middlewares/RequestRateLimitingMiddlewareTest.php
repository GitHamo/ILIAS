<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use DateTimeImmutable;
use ILIAS\ApiGateway\Application\Exceptions\TooManyRequestsException;
use ILIAS\ApiGateway\Application\Factories\RateLimitFactory;
use ILIAS\ApiGateway\Application\Middlewares\RequestRateLimitingMiddleware;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestRateLimitingMiddleware::class)]
class RequestRateLimitingMiddlewareTest extends TestCase
{
    private RequestRateLimitingMiddleware $middleware;
    private MockObject&RateLimitFactory $rateLimitFactory;
    private MockObject&RateLimiting $rateLimitingService;
    private MockObject&Request $requestMock;
    private MockObject&RequestHandler $requestHandlerMock;
    private MockObject&Response $responseMock;
    private ServiceAccess $access;
    private RateLimit $rateLimit;

    #[\Override]
    protected function setUp(): void
    {
        $this->access = new ServiceAccess(
            isEnabled: true,
            isDocsEnabled: true,
            rateLimitUnits: 123,
            rateLimitQuota: 100,
            rateLimitWindow: 60
        );

        $this->middleware = new RequestRateLimitingMiddleware(
            $this->access,
            $this->rateLimitFactory = $this->createMock(RateLimitFactory::class),
            $this->rateLimitingService = $this->createMock(RateLimiting::class),
        );
        $this->requestMock = $this->createMock(Request::class);
        $this->requestHandlerMock = $this->createMock(RequestHandler::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->rateLimit = new RateLimit("foo", "bar", 60, 100);
    }

    public function testProcessesIncomingRequest(): void
    {
        $this->rateLimitFactory
            ->expects(self::once())
            ->method('createFromRequest')
            ->with(
                self::identicalTo($this->requestMock),
            )
            ->willReturn($this->rateLimit);

        $this->rateLimitingService
            ->expects(self::once())
            ->method('consume')
            ->with(
                self::identicalTo($this->rateLimit),
                self::equalTo(123),
                self::isInstanceOf(DateTimeImmutable::class),
            )
            ->willReturn(true);

        $this->requestHandlerMock->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($this->requestMock))
            ->willReturn($this->responseMock);

        $actual = $this->middleware->process($this->requestMock, $this->requestHandlerMock);

        self::assertSame($this->responseMock, $actual);
    }

    public function testThrowsTooManyRequestsExceptionWhenLimitExceeded(): void
    {
        $this->rateLimitFactory
            ->expects(self::once())
            ->method('createFromRequest')
            ->willReturn($this->rateLimit);

        $this->rateLimitingService
            ->expects(self::once())
            ->method('consume')
            ->willReturn(false);

        $this->requestHandlerMock->expects(self::never())->method('handle');

        self::expectException(TooManyRequestsException::class);

        $this->middleware->process($this->requestMock, $this->requestHandlerMock);
    }
}
