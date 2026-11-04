<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\InvalidCredentialsException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Exception;

#[CoversClass(InvalidCredentialsException::class)]
final class InvalidCredentialsExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $message = 'Custom invalid credentials error';
        $previous = new Exception('previous');
        $exception = new InvalidCredentialsException($message, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new InvalidCredentialsException();

        $this->assertSame('Invalid username or password.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
    }
}
