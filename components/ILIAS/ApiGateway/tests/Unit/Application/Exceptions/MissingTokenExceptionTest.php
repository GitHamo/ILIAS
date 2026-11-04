<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use ILIAS\ApiGateway\Application\Exceptions\MissingTokenException;
use ILIAS\HTTP\StatusCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Exception;

#[CoversClass(MissingTokenException::class)]
final class MissingTokenExceptionTest extends TestCase
{
    public function testExceptionState(): void
    {
        $message = 'Custom missing token error';
        $previous = new Exception('previous');
        $exception = new MissingTokenException($message, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues(): void
    {
        $exception = new MissingTokenException();

        $this->assertSame('Authentication token is missing or malformed.', $exception->getMessage());
        $this->assertSame(StatusCode::HTTP_UNAUTHORIZED, $exception->getCode());
    }
}
