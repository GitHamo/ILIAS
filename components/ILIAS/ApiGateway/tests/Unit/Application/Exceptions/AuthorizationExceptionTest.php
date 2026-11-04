<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\AuthorizationException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Exception;

#[CoversClass(AuthorizationException::class)]
final class AuthorizationExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $message = 'Custom authz error';
        $previous = new Exception('previous');
        $exception = new AuthorizationException($message, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_FORBIDDEN, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new AuthorizationException();

        $this->assertSame('Authorization failed.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_FORBIDDEN, $exception->getCode());
    }
}
