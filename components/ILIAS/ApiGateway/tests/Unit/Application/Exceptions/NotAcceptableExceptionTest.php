<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\NotAcceptableException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Exception;

#[CoversClass(NotAcceptableException::class)]
final class NotAcceptableExceptionTest extends TestCase
{
    public function testFormatsMessageCorrectly(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $supportedTypes = 'application/json';
        $previous = new Exception('prev');

        $exception = new NotAcceptableException($request, $supportedTypes, $previous);

        $this->assertSame(StatusCode::HTTP_NOT_ACCEPTABLE, $exception->getCode());
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(
            "This API only supports responses of types: application/json.",
            $exception->getMessage()
        );
    }
}
