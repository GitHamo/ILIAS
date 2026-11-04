<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\InvalidTokenException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Exception;

#[CoversClass(InvalidTokenException::class)]
final class InvalidTokenExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $message = 'Custom invalid token error';
        $previous = new Exception('previous');
        $exception = new InvalidTokenException($message, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new InvalidTokenException();

        $this->assertSame('Authentication token is invalid or expired.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
    }
}
