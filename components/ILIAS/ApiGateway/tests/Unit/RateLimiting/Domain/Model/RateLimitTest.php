<?php

declare(strict_types=1);

namespace Tests\Unit\RateLimiting\Domain\Model;

use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateLimit::class)]
final class RateLimitTest extends TestCase
{
    public function testConstructor(): void
    {
        $rateLimit = new RateLimit(
            fingerprint: 'user123',
            action: 'login',
            windowSeconds: 60,
            maxUnits: 5
        );

        $this->assertSame('user123', $rateLimit->fingerprint);
        $this->assertSame('login', $rateLimit->action);
        $this->assertSame(60, $rateLimit->windowSeconds);
        $this->assertSame(5, $rateLimit->maxUnits);
    }

    public function testConstructorThrowsExceptionForInvalidWindowSeconds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('windowSeconds must be positive');
        new RateLimit('user123', 'login', 0, 5);
    }

    public function testConstructorThrowsExceptionForInvalidMaxUnits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxUnits must be positive');
        new RateLimit('user123', 'login', 60, 0);
    }

    public function testConstructorThrowsExceptionForEmptyFingerprint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fingerprint cannot be empty');
        new RateLimit('', 'login', 60, 5);
    }

    public function testConstructorThrowsExceptionForEmptyAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('action cannot be empty');
        new RateLimit('user123', '', 60, 5);
    }
}
