<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\TooManyRequestsException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Exception;

#[CoversClass(TooManyRequestsException::class)]
final class TooManyRequestsExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $message = 'Custom rate limit message';
        $previous = new Exception('prev');

        $exception = new TooManyRequestsException($request, $message, $previous);

        $this->assertSame(StatusCode::HTTP_TOO_MANY_REQUESTS, $exception->getCode());
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $exception = new TooManyRequestsException($request);

        $this->assertSame('Too many requests.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_TOO_MANY_REQUESTS, $exception->getCode());
    }
}
