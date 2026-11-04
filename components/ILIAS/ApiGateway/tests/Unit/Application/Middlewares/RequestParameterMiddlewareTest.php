<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middlewares;

use ILIAS\ApiGateway\Application\Middlewares\RequestParameterMiddleware;
use ILIAS\ApiGateway\Application\RequestAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UploadedFileInterface;

#[CoversClass(RequestParameterMiddleware::class)]
final class RequestParameterMiddlewareTest extends TestCase
{
    private RequestParameterMiddleware $middleware;
    private ServerRequestInterface&MockObject $requestMock;
    private RequestHandlerInterface&MockObject $handlerMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->middleware = new RequestParameterMiddleware();
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
    }

    /**
        * @param array<int|string, mixed>|null $body
        * @param array<int|string, mixed> $query
        * @param array<int|string, mixed> $expected
     */
    #[DataProvider('parameterProvider')]
    public function testMergesAllParameterCombinations(
        ?array $body,
        array $query,
        array $expected,
    ): void {
        $this->requestMock->method('getParsedBody')->willReturn($body);
        $this->requestMock->method('getQueryParams')->willReturn($query);
        $this->requestMock->expects(self::once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, $expected)
            ->willReturn($requestWithAttr);

        $response = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($requestWithAttr)
            ->willReturn($response);

        self::assertSame($response, $this->middleware->process($this->requestMock, $this->handlerMock));
    }

    /**
    * @return array<string, array{array<int|string, mixed>|null, array<int|string, mixed>, array<int|string, mixed>}>
     */
    public static function parameterProvider(): array
    {
        return [
            'both empty' => [null, [], []],
            'query only' => [null, ['page' => 2], ['page' => 2]],
            'body only' => [['name' => 'Ada'], [], ['name' => 'Ada']],
            'body overrides query' => [['id' => 42], ['id' => 7, 'page' => 2], ['id' => 42, 'page' => 2]],
            'falsey values are preserved' => [
                ['zero' => 0, 'false' => false, 'empty' => ''],
                ['zero' => 1, 'false' => true, 'empty' => 'value'],
                ['zero' => 0, 'false' => false, 'empty' => ''],
            ],
            'nested values are preserved' => [
                ['filter' => ['status' => 'active']],
                ['filter' => ['status' => 'draft'], 'sort' => 'name'],
                ['filter' => ['status' => 'active'], 'sort' => 'name'],
            ],
        ];
    }

    #[DataProvider('multipartContentTypeProvider')]
    public function testReadsUploadedFilesOnlyForMultipartContentTypes(
        string $contentType,
        bool $isMultipart,
    ): void {
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $this->requestMock->method('getQueryParams')->willReturn(['file' => 'query']);
        $this->requestMock->method('getParsedBody')->willReturn(['file' => 'body', 'field' => 'value']);
        $this->requestMock->expects(self::once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn($contentType);

        if ($isMultipart) {
            $this->requestMock->expects(self::once())
                ->method('getUploadedFiles')
                ->willReturn(['file' => $uploadedFile]);
            $expected = ['file' => $uploadedFile, 'field' => 'value'];
        } else {
            $this->requestMock->expects(self::never())->method('getUploadedFiles');
            $expected = ['file' => 'body', 'field' => 'value'];
        }

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, $expected)
            ->willReturn($requestWithAttr);
        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($requestWithAttr)
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function multipartContentTypeProvider(): array
    {
        return [
            'multipart without parameters' => ['multipart/form-data', true],
            'multipart with boundary' => ['multipart/form-data; boundary=test', true],
            'multipart with uppercase media type' => ['MULTIPART/FORM-DATA; boundary=test', true],
            'json' => ['application/json', false],
            'empty content type' => ['', false],
            'similar but different media type' => ['application/multipart/form-data', false],
        ];
    }

    public function testMergesBodyAndQueryParams(): void
    {
        $body = ['a' => 1, 'b' => 2];
        $query = ['b' => 3, 'c' => 4];
        $expectedMerged = ['b' => 2, 'c' => 4, 'a' => 1]; // Body 'b' overrides Query 'b'

        $this->requestMock->method('getParsedBody')->willReturn($body);
        $this->requestMock->method('getQueryParams')->willReturn($query);

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);

        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, self::equalTo($expectedMerged))
            ->willReturn($requestWithAttr);

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($requestWithAttr)
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testBodyOverridesQueryForIntegerKeys(): void
    {
        $this->requestMock->method('getParsedBody')->willReturn([0 => 'body']);
        $this->requestMock->method('getQueryParams')->willReturn([0 => 'query']);
        $this->requestMock->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, [0 => 'body'])
            ->willReturn($requestWithAttr);
        $this->handlerMock->method('handle')->with($requestWithAttr)->willReturn(
            $this->createMock(ResponseInterface::class),
        );

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testUploadedFilesOverrideBodyForIntegerKeys(): void
    {
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $this->requestMock->method('getQueryParams')->willReturn([]);
        $this->requestMock->method('getParsedBody')->willReturn([0 => 'body']);
        $this->requestMock->method('getHeaderLine')->with('Content-Type')->willReturn('multipart/form-data');
        $this->requestMock->expects(self::once())->method('getUploadedFiles')->willReturn([0 => $uploadedFile]);

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, [0 => $uploadedFile])
            ->willReturn($requestWithAttr);
        $this->handlerMock->method('handle')->with($requestWithAttr)->willReturn(
            $this->createMock(ResponseInterface::class),
        );

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testHandlesNullParsedBody(): void
    {
        $query = ['b' => 3, 'c' => 4];
        $expectedMerged = ['b' => 3, 'c' => 4];

        $this->requestMock->method('getParsedBody')->willReturn(null);
        $this->requestMock->method('getQueryParams')->willReturn($query);

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);

        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, self::equalTo($expectedMerged))
            ->willReturn($requestWithAttr);

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($requestWithAttr)
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testHandlesEmptyQueryParams(): void
    {
        $body = ['a' => 1, 'b' => 2];
        $expectedMerged = ['a' => 1, 'b' => 2];

        $this->requestMock->method('getParsedBody')->willReturn($body);
        $this->requestMock->method('getQueryParams')->willReturn([]);

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);

        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, self::equalTo($expectedMerged))
            ->willReturn($requestWithAttr);

        $this->handlerMock->expects(self::once())
            ->method('handle')
            ->with($requestWithAttr)
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testMergesUploadedFilesForMultipartRequests(): void
    {
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $expectedMerged = ['field' => 'value', 'file' => $uploadedFile];

        $this->requestMock->method('getQueryParams')->willReturn([]);
        $this->requestMock->method('getParsedBody')->willReturn(['field' => 'value']);
        $this->requestMock->expects(self::once())->method('getHeaderLine')->with('Content-Type')->willReturn(
            'multipart/form-data; boundary=test',
        );
        $this->requestMock->expects(self::once())
            ->method('getUploadedFiles')
            ->willReturn(['file' => $uploadedFile]);

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, self::equalTo($expectedMerged))
            ->willReturn($requestWithAttr);
        $this->handlerMock->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }

    public function testDoesNotReadUploadedFilesForNonMultipartRequests(): void
    {
        $expectedMerged = ['field' => 'value'];

        $this->requestMock->method('getQueryParams')->willReturn([]);
        $this->requestMock->method('getParsedBody')->willReturn($expectedMerged);
        $this->requestMock->expects(self::once())->method('getHeaderLine')->with('Content-Type')->willReturn(
            'application/json',
        );
        $this->requestMock->expects(self::never())->method('getUploadedFiles');

        $requestWithAttr = $this->createMock(ServerRequestInterface::class);
        $this->requestMock->expects(self::once())
            ->method('withAttribute')
            ->with(RequestAttributes::PARSED_PARAMETERS, self::equalTo($expectedMerged))
            ->willReturn($requestWithAttr);
        $this->handlerMock->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $this->middleware->process($this->requestMock, $this->handlerMock);
    }
}
