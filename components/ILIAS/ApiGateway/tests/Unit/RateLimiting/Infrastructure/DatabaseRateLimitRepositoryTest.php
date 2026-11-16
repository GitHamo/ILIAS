<?php

declare(strict_types=1);

namespace Tests\Unit\RateLimiting\Infrastructure;

use DateTimeImmutable;
use ilDBConstants;
use ilDBStatement;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\DatabaseRateLimitRepository;
use ILIAS\Database\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseRateLimitRepository::class)]
class DatabaseRateLimitRepositoryTest extends TestCase
{
    private const string FINGERPRINT = '203.0.113.5';
    private const string SCOPE = 'login';
    private const string SINCE = '2030-01-02 03:04:05';

    private Connection&Stub $connection;
    private DatabaseRateLimitRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createStub(Connection::class);
        $this->repository = new DatabaseRateLimitRepository($this->connection);
    }

    public function testSumsUnitsAsInteger(): void
    {
        // The database driver returns SUM as string.
        $this->connection->method('queryF')->willReturn($this->createStub(ilDBStatement::class));
        $this->connection->method('fetchAssoc')->willReturn(['total' => '7']);

        $total = $this->repository->sumUnits(self::FINGERPRINT, self::SCOPE, new DateTimeImmutable(self::SINCE));

        self::assertSame(7, $total);
    }

    public function testSumsToZeroWhenNothingRecorded(): void
    {
        $this->connection->method('queryF')->willReturn($this->createStub(ilDBStatement::class));
        $this->connection->method('fetchAssoc')->willReturn(null);

        $total = $this->repository->sumUnits(self::FINGERPRINT, self::SCOPE, new DateTimeImmutable(self::SINCE));

        self::assertSame(0, $total);
    }

    public function testSumsOnlyForGivenClientScopeAndWindow(): void
    {
        $captured = ['types' => [], 'values' => []];
        $this->connection->method('fetchAssoc')->willReturn(['total' => '0']);
        $this->connection->method('queryF')->willReturnCallback(
            function (string $query, array $types, array $values) use (&$captured): ilDBStatement {
                $captured = ['types' => $types, 'values' => $values];

                return $this->createStub(ilDBStatement::class);
            }
        );

        $this->repository->sumUnits(self::FINGERPRINT, self::SCOPE, new DateTimeImmutable(self::SINCE));

        self::assertSame(
            [ilDBConstants::T_TEXT, ilDBConstants::T_TEXT, ilDBConstants::T_TIMESTAMP],
            $captured['types'],
        );
        self::assertSame([self::FINGERPRINT, self::SCOPE, self::SINCE], $captured['values']);
    }

    public function testStoresEntryWithNextId(): void
    {
        $nextId = 55;
        $captured = [];
        $connection = $this->createMock(Connection::class);
        $connection->method('nextId')->willReturn($nextId);
        $connection->expects($this->once())->method('manipulateF')->willReturnCallback(
            function (string $query, array $types, array $values) use (&$captured): int {
                $captured = $values;

                return 1;
            }
        );

        (new DatabaseRateLimitRepository($connection))->store(
            new RateLimitEntry(self::FINGERPRINT, self::SCOPE, 3, new DateTimeImmutable(self::SINCE))
        );

        self::assertSame([$nextId, self::FINGERPRINT, self::SCOPE, 3, self::SINCE], $captured);
    }

    public function testDeletesExpiredEntriesUpToNowAndReturnsCount(): void
    {
        $captured = [];
        $this->connection->method('manipulateF')->willReturnCallback(
            function (string $query, array $types, array $values) use (&$captured): int {
                $captured = $values;

                return 4;
            }
        );

        $deleted = $this->repository->deleteExpired(new DateTimeImmutable(self::SINCE));

        self::assertSame(4, $deleted);
        self::assertSame([self::SINCE], $captured);
    }
}
