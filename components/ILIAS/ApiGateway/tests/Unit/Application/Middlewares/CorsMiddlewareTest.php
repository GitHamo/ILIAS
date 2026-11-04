<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Middlewares\CorsMiddleware;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CorsMiddleware::class)]
class CorsMiddlewareTest extends TestCase
{
    private MockObject&ResponseFactory $responseFactory;
    private MockObject&ServerRequestInterface $request;
    private MockObject&RequestHandlerInterface $handler;
    private MockObject&ResponseInterface $response;

    private CorsMiddleware $middleware;

    #[\Override]
    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->middleware = new CorsMiddleware($this->responseFactory);
    }

    public function testProcessAllowsAnyOriginWithWildcard(): void
    {
        $origin = 'https://any-random-domain.com';

        $this->request->method('getHeaderLine')
            ->willReturnMap([
                ['Origin', $origin],
            ]);
        $this->request->method('getMethod')->willReturn('GET');

        $this->handler->method('handle')->willReturn($this->response);

        $this->response->expects($this->exactly(5))
            ->method('withHeader')
            ->willReturnMap([
                ['Access-Control-Allow-Origin', '*', $this->response],
                ['Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS', $this->response],
                ['Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept', $this->response],
                ['Access-Control-Max-Age', '86400', $this->response],
                ['Vary', 'Origin', $this->response],
            ]);

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testProcessReturns204OnPreflightRequestWithWildcard(): void
    {
        $origin = 'https://some-domain.org';

        $this->request->method('getMethod')->willReturn('OPTIONS');
        $this->request->method('getHeaderLine')
            ->willReturnMap([
                ['Origin', $origin],
                ['Access-Control-Request-Method', 'POST'],
            ]);

        $preflightResponse = $this->createMock(ResponseInterface::class);
        $preflightResponse->method('withHeader')->willReturnSelf();
        $preflightResponse->method('withStatus')->willReturnSelf();

        $this->responseFactory->method('create')->willReturn($preflightResponse);

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($preflightResponse, $actual);
    }

    public function testProcessSkipsIfNoOriginHeader(): void
    {
        $this->request->method('getHeaderLine')->with('Origin')->willReturn('');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testProcessSkipsIfOriginHeaderIsWhitespaceOnly(): void
    {
        $this->request->method('getHeaderLine')->with('Origin')->willReturn('   ');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testProcessSkipsPreflightIfRequestedMethodNotAllowed(): void
    {
        $origin = 'https://example.com';

        $this->request->method('getMethod')->willReturn('OPTIONS');
        $this->request->method('getHeaderLine')
            ->willReturnMap([
                ['Origin', $origin],
                ['Access-Control-Request-Method', 'TRACE'],
            ]);

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->response);

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }
}
