<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\AuthenticationException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Exception;

#[CoversClass(AuthenticationException::class)]
final class AuthenticationExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $message = 'Custom auth error';
        $previous = new Exception('previous');
        $exception = new AuthenticationException($message, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new AuthenticationException();

        $this->assertSame('Authentication failed.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
    }
}
