<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Middlewares\BrowserSecurityMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(BrowserSecurityMiddleware::class)]
class BrowserSecurityMiddlewareTest extends TestCase
{
    private BrowserSecurityMiddleware $middleware;
    private ServerRequestInterface&MockObject $request;
    private RequestHandlerInterface&MockObject $handler;
    private ResponseInterface&MockObject $responseMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->middleware = new BrowserSecurityMiddleware();

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
    }

    public function testItAddsSecurityHeaders(): void
    {
        $this->handler->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($this->request))
            ->willReturn($this->responseMock);

        $this->responseMock->expects(self::exactly(4))
            ->method('withHeader')
            ->willReturnCallback(function (string $name, string $value) {
                static $calls = 0;
                $expected = [
                    ['Content-Security-Policy', "default-src 'self'; frame-ancestors 'none';"],
                    ['X-Content-Type-Options', 'nosniff'],
                    ['X-Frame-Options', 'DENY'],
                    ['X-XSS-Protection', '0'],
                ];

                $this->assertSame($expected[$calls][0], $name);
                $this->assertSame($expected[$calls][1], $value);

                $calls++;

                return $this->responseMock;
            });

        $actual = $this->middleware->process($this->request, $this->handler);

        $this->assertSame($this->responseMock, $actual);
    }
}
