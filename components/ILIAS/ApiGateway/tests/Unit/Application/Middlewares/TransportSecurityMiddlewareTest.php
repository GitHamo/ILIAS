<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Middlewares\TransportSecurityMiddleware;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(TransportSecurityMiddleware::class)]
class TransportSecurityMiddlewareTest extends TestCase
{
    private MockObject&ServerRequestInterface $request;
    private MockObject&RequestHandlerInterface $handler;
    private MockObject&UriInterface $uriMock;
    private MockObject&ResponseInterface $response;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = $this->createConfiguredMock(ServerRequestInterface::class, [
            'getUri' => $this->uriMock = $this->createMock(UriInterface::class),
        ]);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->handler = $this->createConfiguredMock(RequestHandlerInterface::class, [
            'handle' => $this->response,
        ]);
    }

    public function testHeaderIsAddedWhenBaseUrlIsHttps(): void
    {
        $identity = $this->createIdentity('HtTpS://www.example.org');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('https');

        $this->response->expects(self::once())
            ->method('withHeader')
            ->with('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->willReturn($this->response);

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testHeaderIsNotAddedWhenBaseUrlIsHttp(): void
    {
        $identity = $this->createIdentity('http://www.example.org');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('http');

        $this->response->expects(self::never())->method('withHeader');

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testHeaderIsNotAddedWhenBaseUrlHasNoScheme(): void
    {
        $identity = $this->createIdentity('www.example.org');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('http');

        $this->response->expects(self::never())->method('withHeader');

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testHeaderIsNotAddedWhenBaseUrlIsEmpty(): void
    {
        $identity = $this->createIdentity('');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('http');

        $this->response->expects(self::never())->method('withHeader');

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testHeaderIsNotAddedWhenRequestIsNotHttpsEvenIfBaseUrlIsConfiguredForHttps(): void
    {
        $identity = $this->createIdentity('https://www.example.org');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('http');

        $this->response->expects(self::never())->method('withHeader');

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    public function testHeaderIsNotAddedWhenRequestSchemeIsNullEvenIfBaseUrlIsConfiguredForHttps(): void
    {
        $identity = $this->createIdentity('https://www.example.org');
        $middleware = new TransportSecurityMiddleware($identity);
        $this->uriMock->method('getScheme')->willReturn('');

        $this->response->expects(self::never())->method('withHeader');

        $actual = $middleware->process($this->request, $this->handler);

        $this->assertSame($this->response, $actual);
    }

    private function createIdentity(string $baseUrl): ServiceIdentity
    {
        return new ServiceIdentity(ServiceProtocol::REST, $baseUrl, ServiceProtocol::REST->value);
    }
}
