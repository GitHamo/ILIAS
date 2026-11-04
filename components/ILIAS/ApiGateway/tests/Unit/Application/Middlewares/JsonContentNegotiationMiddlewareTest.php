<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Exceptions\NotAcceptableException;
use ILIAS\ApiGateway\Application\Middlewares\JsonContentNegotiationMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(JsonContentNegotiationMiddleware::class)]
final class JsonContentNegotiationMiddlewareTest extends TestCase
{
    private JsonContentNegotiationMiddleware $middleware;
    private MockObject&ServerRequestInterface $requestMock;
    private MockObject&RequestHandlerInterface $handlerMock;
    private MockObject&ResponseInterface $responseMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->middleware = new JsonContentNegotiationMiddleware();
    }

    #[DataProvider('compatibleHeadersProvider')]
    public function testProcessProceedsWhenJsonIsAccepted(string $acceptHeader): void
    {
        $this->requestMock->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($acceptHeader);

        $this->handlerMock->expects($this->once())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturn($this->responseMock);

        $response = $this->middleware->process($this->requestMock, $this->handlerMock);

        $this->assertSame($this->responseMock, $response);
    }

    /**
     * @return array<string, string[]>
     */
    public static function compatibleHeadersProvider(): array
    {
        return [
            'empty header' => [''],
            'whitespace header' => ['  '],
            'wildcard all' => ['*/*'],
            'wildcard application' => ['application/*'],
            'exact match' => ['application/json'],
            'case insensitive' => ['APPLICATION/JSON'],
            'with parameters' => ['application/json; charset=utf-8'],
            'multiple types' => ['text/html, application/json'],
            'quality factor' => ['application/json; q=0.5'],
            'mixed case and parameters' => ['Application/Json; q=1.0'],
            'case insensitive wildcard' => ['APPLICATION/*'],
        ];
    }

    #[DataProvider('incompatibleHeadersProvider')]
    public function testProcessThrowsExceptionWhenJsonIsNotAccepted(string $acceptHeader): void
    {
        $this->requestMock->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($acceptHeader);

        $this->handlerMock->expects($this->never())
            ->method('handle');

        $this->expectException(NotAcceptableException::class);
        // The message is defined in the exception class
        $this->expectExceptionMessage('This API only supports responses of types: application/json');

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    /**
     * @return array<string, string[]>
     */
    public static function incompatibleHeadersProvider(): array
    {
        return [
            'incompatible type' => ['text/html'],
            'incompatible application type' => ['application/xml'],
            'partial match should fail' => ['application/json-patch+json'],
            'different subtype' => ['application/vnd.api+json'],
            'invalid numeric header' => ['0'],
        ];
    }
}
