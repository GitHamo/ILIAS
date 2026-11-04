<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Middlewares\ApiServiceStatusMiddleware;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\HTTP\Response\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiServiceStatusMiddleware::class)]
class ApiServiceStatusMiddlewareTest extends TestCase
{
    private MockObject&ResponseFactory $responseFactoryMock;
    private MockObject&ServerRequestInterface $requestMock;
    private MockObject&ResponseInterface $responseMock;
    private MockObject&RequestHandlerInterface $handlerMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->responseFactoryMock = $this->createMock(ResponseFactory::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
    }

    public function testServesRequestIfWebserviceIsEnabled(): void
    {
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $middleware = new ApiServiceStatusMiddleware($access, $this->responseFactoryMock);

        $this->responseFactoryMock->expects(self::never())->method('create');

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturn($this->responseMock);

        $actual = $middleware->process($this->requestMock, $this->handlerMock);

        self::assertSame($this->responseMock, $actual);
    }

    public function testExitIfWebserviceIsDisabled(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $access = new ServiceAccess(false, true, 1, 100, 60);
        $middleware = new ApiServiceStatusMiddleware($access, $this->responseFactoryMock);

        $this->responseFactoryMock->expects(self::once())->method('create')->willReturn($responseMock);

        $responseMock->expects(self::once())
            ->method('withStatus')
            ->with(self::identicalTo(503))
            ->willReturnSelf();

        $responseMock->expects(self::once())
            ->method('withHeader')
            ->with(
                self::identicalTo('Content-Type'),
                self::identicalTo('text/plain'),
            )
            ->willReturnSelf();

        $responseMock->method('getBody')->willReturn($streamMock);

        $streamMock->expects(self::once())
            ->method('write')
            ->with(self::identicalTo('API Service is currently disabled.'));

        $streamMock->expects(self::once())
            ->method('rewind');

        $actual = $middleware->process($this->requestMock, $this->handlerMock);

        self::assertSame($responseMock, $actual);
    }
}
