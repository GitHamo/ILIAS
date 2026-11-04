<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Exceptions\InvalidJsonException;
use ILIAS\ApiGateway\Application\Exceptions\MissingJsonPayloadException;
use ILIAS\ApiGateway\Application\Middlewares\JsonBodyParserMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(JsonBodyParserMiddleware::class)]
final class JsonBodyParserMiddlewareTest extends TestCase
{
    private JsonBodyParserMiddleware $middleware;
    private MockObject&ServerRequestInterface $requestMock;
    private MockObject&RequestHandlerInterface $handlerMock;
    private MockObject&ResponseInterface $responseMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->middleware = new JsonBodyParserMiddleware();
    }

    public function testProcessSkipsIfContentTypeIsNotJson(): void
    {
        $this->requestMock->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/plain');

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturn($this->responseMock);

        $response = $this->middleware->process($this->requestMock, $this->handlerMock);

        $this->assertSame($this->responseMock, $response);
    }

    #[DataProvider('methodsWithoutBodyProvider')]
    public function testProcessSkipsIfMethodDoesNotExpectBody(string $method): void
    {
        $this->requestMock->method('getMethod')->willReturn($method);
        $this->requestMock->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->willReturn($this->responseMock);

        $response = $this->middleware->process($this->requestMock, $this->handlerMock);

        $this->assertSame($this->responseMock, $response);
    }

    /**
     * @return array<int, string[]>
     */
    public static function methodsWithoutBodyProvider(): array
    {
        return [
            ['GET'],
            ['DELETE'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }

    #[DataProvider('validJsonProvider')]
    public function testProcessParsesValidJsonBody(string $json, array $expectedData): void
    {
        $this->requestMock->method('getMethod')->willReturn('POST');
        $this->requestMock->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('isSeekable')->willReturn(true);
        $bodyMock->expects(self::once())->method('rewind');
        $bodyMock->method('getContents')->willReturn($json);
        $this->requestMock->method('getBody')->willReturn($bodyMock);

        $this->requestMock->expects(self::once())
            ->method('withParsedBody')
            ->with($expectedData)
            ->willReturn($this->requestMock);

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->willReturn($this->responseMock);

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function validJsonProvider(): array
    {
        return [
            'simple object' => ['{"foo": "bar"}', ['foo' => 'bar']],
            'nested object' => ['{"a": {"b": 1}}', ['a' => ['b' => 1]]],
            'empty object' => ['{}', []],
        ];
    }

    #[DataProvider('invalidJsonProvider')]
    public function testProcessThrowsExceptionOnInvalidJson(string $invalidJson, string $expectedException): void
    {
        $this->requestMock->method('getMethod')->willReturn('POST');
        $this->requestMock->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('isSeekable')->willReturn(false);
        $bodyMock->method('getContents')->willReturn($invalidJson);
        $this->requestMock->method('getBody')->willReturn($bodyMock);

        $this->expectException($expectedException);

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidJsonProvider(): array
    {
        return [
            'malformed json' => ['{invalid}', InvalidJsonException::class],
            'json scalar string' => ['"string"', InvalidJsonException::class],
            'json scalar bool' => ['true', InvalidJsonException::class],
            'empty body' => ['', MissingJsonPayloadException::class],
            'whitespace body' => ['   ', MissingJsonPayloadException::class],
            'zero body' => ['0', InvalidJsonException::class],
        ];
    }
}
