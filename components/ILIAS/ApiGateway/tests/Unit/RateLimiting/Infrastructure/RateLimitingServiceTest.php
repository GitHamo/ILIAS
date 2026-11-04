<?php

declare(strict_types=1);

namespace Tests\Unit\RateLimiting\Infrastructure;

use DateTimeImmutable;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimitRepository;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\RateLimitingService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateLimitingService::class)]

class RateLimitingServiceTest extends TestCase
{
    private RateLimitingService $service;
    private MockObject&RateLimitRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new RateLimitingService(
            $this->repository = $this->createMock(RateLimitRepository::class),
        );
    }

    public function testConsumesExactlyUpToLimit(): void
    {
        $units = 5;
        $limit = new RateLimit('fingerprint', 'action', 60, 10);
        $now = $this->createMock(DateTimeImmutable::class);
        $since = $this->createMock(DateTimeImmutable::class);

        $sumUnits = 2;

        $repositoryArgs = [
            self::identicalTo($limit->fingerprint),
            self::identicalTo($limit->action),
            self::identicalTo($since),
        ];

        $rateLimitEntry = new RateLimitEntry(
            $limit->fingerprint,
            $limit->action,
            $units,
            $now,
        );

        $now->expects($this->once())
            ->method('modify')
            ->with(self::identicalTo("-{$limit->windowSeconds} seconds"))
            ->willReturn($since);

        $this->repository
            ->expects($this->once())
            ->method('sumUnits')
            ->with(...$repositoryArgs)
            ->willReturn($sumUnits);

        $this->repository
            ->expects($this->once())
            ->method('store')
            ->with(self::equalTo($rateLimitEntry));

        $actual = $this->service->consume($limit, $units, $now);

        self::assertTrue($actual);
    }

    public function testDoesNotConsumeUnitsInCaseOfExceeded(): void
    {
        $units = 5;
        $limit = new RateLimit('fingerprint', 'action', 20, 10);
        $now = $this->createConfiguredMock(DateTimeImmutable::class, [
            'modify' => $since = $this->createMock(DateTimeImmutable::class),
        ]);

        $sumUnits = 9;

        $this->repository
            ->expects($this->once())
            ->method('sumUnits')
            ->willReturn($sumUnits);

        $this->repository->expects($this->never())->method('store');

        $actual = $this->service->consume($limit, $units, $now);

        self::assertFalse($actual);
    }

    public function testPurgesExpiredLimits(): void
    {
        $now = $this->createMock(DateTimeImmutable::class);
        $expected = 123;

        $this->repository
            ->expects($this->once())
            ->method('deleteExpired')
            ->with($now)
            ->willReturn($expected);

        $actual = $this->service->purgeExpired($now);

        self::assertSame($expected, $actual);
    }
}
