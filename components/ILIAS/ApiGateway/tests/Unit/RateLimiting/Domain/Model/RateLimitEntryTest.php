<?php

declare(strict_types=1);

namespace Tests\Unit\RateLimiting\Domain\Model;

use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateLimitEntry::class)]
final class RateLimitEntryTest extends TestCase
{
    public function testConstructor(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-03 12:00:00');
        $entry = new RateLimitEntry(
            fingerprint: 'user123',
            scope: 'login',
            units: 5,
            createdAt: $createdAt
        );

        $this->assertSame('user123', $entry->fingerprint);
        $this->assertSame('login', $entry->scope);
        $this->assertSame(5, $entry->units);
        $this->assertSame($createdAt, $entry->createdAt);
    }
}
