<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use ILIAS\ApiGateway\Application\RouteExecutor;
use ILIAS\ApiGateway\Contracts\Payload;
use ILIAS\ApiGateway\Contracts\Webservice;
use ILIAS\ApiGateway\Routing\RouteHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;

class RouteExecutorTest extends TestCase
{
    private RouteExecutor $executor;
    private Webservice&MockObject $webserviceMock;
    private Request&MockObject $requestMock;
    private Response&MockObject $responseMock;
    private StreamInterface&MockObject $streamMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->webserviceMock = $this->createMock(Webservice::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->responseMock = $this->createMock(Response::class);
        $this->streamMock = $this->createMock(StreamInterface::class);

        $this->responseMock->method('getBody')->willReturn($this->streamMock);

        $this->executor = new RouteExecutor($this->webserviceMock);
    }

    public function testDispatchSuccessfullyHandlesRequestAndWritesResponse(): void
    {
        $queryParams = ['queryParam' => 'queryValue', 'routeParam' => 'routeValue'];
        $routeArgs = ['routeParam' => 'routeValue-override'];
        $mergedParams = array_merge($queryParams, $routeArgs);
        $handlerResponseBody = ['handler' => 'response'];
        $serviceResponseBody = '{"service":"response"}';

        $this->requestMock->method('getQueryParams')->willReturn($queryParams);

        $routeHandlerMock = $this->createMock(RouteHandler::class);

        $routeHandlerMock->method('__invoke')
            ->with($this->callback(function (array $params) use ($mergedParams): bool {
                self::assertEquals($mergedParams, $params);

                return true;
            }))
            ->willReturn($handlerResponseBody);

        $originalPayload = new Payload(data: $handlerResponseBody);
        $payload = new Payload(
            data: $handlerResponseBody,
            headers: [
                'foo' => 'bar',
            ],
            body: $serviceResponseBody,
        );

        $this->webserviceMock->method('handle')
            ->with(
                self::equalTo($originalPayload),
            )
            ->willReturn($payload);

        $this->streamMock->expects(self::once())
            ->method('write')
            ->with($serviceResponseBody);

        $this->responseMock
            ->expects(self::once())
            ->method('withHeader')
            ->with(
                self::identicalTo('foo'),
                self::identicalTo('bar'),
            )
            ->willReturn($this->responseMock)
        ;

        $response = ($this->executor)(
            $this->requestMock,
            $this->responseMock,
            $routeArgs,
            $routeHandlerMock,
        );

        self::assertSame($this->responseMock, $response);
    }
}
