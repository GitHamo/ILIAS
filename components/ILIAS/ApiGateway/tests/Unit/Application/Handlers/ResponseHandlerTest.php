<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Handlers;

use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Application\RequestAttributes;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Webservice\Domain\Model\Payload;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamInterface;

use function array_merge;

class ResponseHandlerTest extends TestCase
{
    private ResponseHandler $handler;
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

        $this->handler = new ResponseHandler($this->webserviceMock);
    }

    public function testDispatchSuccessfullyHandlesRequestAndWritesResponse(): void
    {
        $mergedParams = ['routeParam' => 'routeValue-override', 'queryParam' => 'queryValue'];
        $routeArgs = ['routeParam' => 'routeValue-override'];
        $handlerResponseBody = ['handler' => 'response'];
        $serviceResponseBody = '{"service":"response"}';
        $userMock = $this->createMock(AuthUser::class);

        $finalRequest = $this->createMock(Request::class);
        $finalRequest->method('getAttribute')->with(RequestAttributes::PARSED_PARAMETERS)->willReturn($mergedParams);

        $this->requestMock->method('getAttribute')
            ->willReturnMap([
                [RequestAttributes::AUTH_USER, null, $userMock],
                [RequestAttributes::PARSED_PARAMETERS, [], $mergedParams],
            ]);
        $this->requestMock->expects(self::once())
            ->method('withoutAttribute')
            ->with(self::identicalTo(RequestAttributes::AUTH_USER))
            ->willReturn($finalRequest);

        $actionMock = $this->createMock(Action::class);

        $actionMock->method('__invoke')
            ->with(
                $this->callback(function (array $params) use ($mergedParams, $routeArgs): bool {
                    self::assertEquals(array_merge($mergedParams, $routeArgs), $params);
                    return true;
                }),
                self::identicalTo($userMock)
            )
            ->willReturn($handlerResponseBody);

        $payload = new Payload(
            data: $handlerResponseBody,
            headers: [
                'foo' => 'bar',
            ],
            body: $serviceResponseBody,
        );

        $this->webserviceMock->method('transform')
            ->with(
                self::equalTo($handlerResponseBody),
            )
            ->willReturn($payload);

        $this->streamMock->expects(self::once())
            ->method('write')
            ->with($serviceResponseBody);

        $this->streamMock->expects(self::once())
            ->method('rewind');

        $this->responseMock
            ->expects(self::once())
            ->method('withHeader')
            ->with(
                self::identicalTo('foo'),
                self::identicalTo('bar'),
            )
            ->willReturn($this->responseMock)
        ;

        $response = ($this->handler)(
            $this->requestMock,
            $this->responseMock,
            $routeArgs,
            $actionMock,
        );

        self::assertSame($this->responseMock, $response);
    }

    public function testHandlesRequestWithParsedBody(): void
    {
        $params = ['body' => 'b_val', 'query' => 'q_val'];
        $routeArgs = ['route' => 'r_val'];
        $expectedFinalParams = array_merge($params, $routeArgs);
        $handlerResponseBody = ['handler' => 'response'];
        $serviceResponseBody = '{"service":"response"}';

        $this->requestMock->method('getAttribute')
            ->willReturnMap([
                [RequestAttributes::AUTH_USER, null, null],
                [RequestAttributes::PARSED_PARAMETERS, [], $params],
            ]);
        $this->requestMock->method('withoutAttribute')->with(RequestAttributes::AUTH_USER)->willReturn($this->requestMock);

        $actionMock = $this->createMock(Action::class);
        $actionMock->expects(self::once())
            ->method('__invoke')
            ->with($expectedFinalParams, null)
            ->willReturn($handlerResponseBody);

        $payload = new Payload(
            data: $handlerResponseBody,
            headers: [
                'foo' => 'bar',
            ],
            body: $serviceResponseBody,
        );

        $this->webserviceMock->method('transform')
            ->with(self::equalTo($handlerResponseBody))
            ->willReturn($payload);

        $this->streamMock->expects(self::once())
            ->method('write')
            ->with($serviceResponseBody);

        $this->streamMock->expects(self::once())
            ->method('rewind');

        $this->responseMock
            ->expects(self::once())
            ->method('withHeader')
            ->with(
                self::identicalTo('foo'),
                self::identicalTo('bar'),
            )
            ->willReturn($this->responseMock);

        $response = ($this->handler)(
            $this->requestMock,
            $this->responseMock,
            $routeArgs,
            $actionMock
        );

        self::assertSame($this->responseMock, $response);
    }

    public function testHandlesRequestWithNullParsedBody(): void
    {
        $params = ['query' => 'q_val'];
        $routeArgs = ['route' => 'r_val'];
        $expectedFinalParams = array_merge($params, $routeArgs);
        $handlerResponseBody = ['handler' => 'response'];
        $serviceResponseBody = '{"service":"response"}';

        $this->requestMock->method('getAttribute')
            ->willReturnMap([
                [RequestAttributes::AUTH_USER, null, null],
                [RequestAttributes::PARSED_PARAMETERS, [], $params],
            ]);
        $this->requestMock->method('withoutAttribute')->with(RequestAttributes::AUTH_USER)->willReturn($this->requestMock);

        $actionMock = $this->createMock(Action::class);
        $actionMock->expects(self::once())
            ->method('__invoke')
            ->with($expectedFinalParams, null)
            ->willReturn($handlerResponseBody);

        $payload = new Payload(
            data: $handlerResponseBody,
            headers: [
                'baz' => 'qux',
            ],
            body: $serviceResponseBody,
        );

        $this->webserviceMock->method('transform')
            ->with(self::equalTo($handlerResponseBody))
            ->willReturn($payload);

        $this->streamMock->expects(self::once())
            ->method('write')
            ->with($serviceResponseBody);

        $this->streamMock->expects(self::once())
            ->method('rewind');

        $this->responseMock
            ->expects(self::once())
            ->method('withHeader')
            ->with(
                self::identicalTo('baz'),
                self::identicalTo('qux'),
            )
            ->willReturn($this->responseMock);

        $response = ($this->handler)(
            $this->requestMock,
            $this->responseMock,
            $routeArgs,
            $actionMock
        );

        self::assertSame($this->responseMock, $response);
    }
}
