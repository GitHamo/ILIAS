<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use DateTimeImmutable;
use ilDBConstants;
use ilDBStatement;
use ILIAS\ApiGateway\Auth\Domain\Model\RefreshToken;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseRefreshTokenRepository;
use ILIAS\Database\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseRefreshTokenRepository::class)]
class DatabaseRefreshTokenRepositoryTest extends TestCase
{
    private const int USER_ID = 42;
    private const int TOKEN_ID = 7;
    private const string TOKEN_HASH = 'hashed-token';
    private const string EXPIRES_AT = '2030-01-02 03:04:05';

    private Connection&Stub $connection;
    private DatabaseRefreshTokenRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createStub(Connection::class);
        $this->repository = new DatabaseRefreshTokenRepository($this->connection);
    }

    public function testFindsNothingForEmptyHash(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('queryF');

        $repository = new DatabaseRefreshTokenRepository($connection);

        self::assertNull($repository->findByHash(''));
    }

    public function testFindsNothingWhenHashIsUnknown(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow(null, 0));

        self::assertNull($this->repository->findByHash('unknown'));
    }

    public function testFindsNothingWhenRowCannotBeFetched(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow(null, 1));

        self::assertNull($this->repository->findByHash(self::TOKEN_HASH));
    }

    public function testMapsStoredRowToActiveRefreshToken(): void
    {
        // The database driver returns every column as string.
        $this->connection->method('queryF')->willReturn($this->statementWithRow([
            'id' => (string) self::TOKEN_ID,
            'user_id' => (string) self::USER_ID,
            'token_hash' => self::TOKEN_HASH,
            'is_revoked' => '0',
            'expires_at' => self::EXPIRES_AT,
        ]));

        $token = $this->repository->findByHash(self::TOKEN_HASH);

        self::assertInstanceOf(RefreshToken::class, $token);
        self::assertSame(self::TOKEN_ID, $token->getId());
        self::assertSame(self::USER_ID, $token->getUserId());
        self::assertSame(self::TOKEN_HASH, $token->getTokenHash());
        self::assertSame(self::EXPIRES_AT, $token->getExpiresAt()->format('Y-m-d H:i:s'));
        self::assertFalse($token->isRevoked());
        self::assertFalse($token->isExpired());
    }

    public function testMapsStoredRowToRevokedRefreshToken(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow([
            'id' => (string) self::TOKEN_ID,
            'user_id' => (string) self::USER_ID,
            'token_hash' => self::TOKEN_HASH,
            'is_revoked' => '1',
            'expires_at' => self::EXPIRES_AT,
        ]));

        $token = $this->repository->findByHash(self::TOKEN_HASH);

        self::assertTrue($token?->isRevoked());
    }

    public function testMapsStoredRowToExpiredRefreshToken(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow([
            'id' => (string) self::TOKEN_ID,
            'user_id' => (string) self::USER_ID,
            'token_hash' => self::TOKEN_HASH,
            'is_revoked' => '0',
            'expires_at' => '2000-01-01 00:00:00',
        ]));

        $token = $this->repository->findByHash(self::TOKEN_HASH);

        self::assertTrue($token?->isExpired());
    }

    public function testLooksUpTokenByHashOnly(): void
    {
        $captured = ['types' => [], 'values' => []];
        $this->connection->method('queryF')->willReturnCallback(
            function (string $query, array $types, array $values) use (&$captured): ilDBStatement {
                $captured = ['types' => $types, 'values' => $values];

                return $this->statementWithRow(null, 0);
            }
        );

        $this->repository->findByHash(self::TOKEN_HASH);

        self::assertSame([ilDBConstants::T_TEXT], $captured['types']);
        self::assertSame([self::TOKEN_HASH], $captured['values']);
    }

    public function testInsertsNewTokenWithNextIdAndNotRevoked(): void
    {
        $nextId = 99;
        /** @var array<string, array{0: string, 1: int|string}> $captured */
        $captured = [];
        $connection = $this->createMock(Connection::class);
        $connection->method('nextId')->willReturn($nextId);
        $connection->expects($this->never())->method('update');
        $connection->expects($this->once())->method('insert')->willReturnCallback(
            function (string $table, array $values) use (&$captured): int {
                $captured = $values;

                return 1;
            }
        );

        (new DatabaseRefreshTokenRepository($connection))->save(
            new RefreshToken(self::USER_ID, self::TOKEN_HASH, new DateTimeImmutable(self::EXPIRES_AT))
        );

        self::assertSame($nextId, $captured['id'][1]);
        self::assertSame(self::USER_ID, $captured['user_id'][1]);
        self::assertSame(self::TOKEN_HASH, $captured['token_hash'][1]);
        self::assertSame(0, $captured['is_revoked'][1]);
        self::assertSame(self::EXPIRES_AT, $captured['expires_at'][1]);
    }

    public function testUpdatesExistingTokenWhenRevoked(): void
    {
        /** @var array<string, array{0: string, 1: int|string}> $capturedValues */
        $capturedValues = [];
        /** @var array<string, array{0: string, 1: int|string}> $capturedWhere */
        $capturedWhere = [];
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->once())->method('update')->willReturnCallback(
            function (string $table, array $values, array $where) use (&$capturedValues, &$capturedWhere): int {
                $capturedValues = $values;
                $capturedWhere = $where;

                return 1;
            }
        );

        $token = new RefreshToken(
            self::USER_ID,
            self::TOKEN_HASH,
            new DateTimeImmutable(self::EXPIRES_AT),
            self::TOKEN_ID,
        );
        $token->revoke();

        (new DatabaseRefreshTokenRepository($connection))->save($token);

        self::assertSame(1, $capturedValues['is_revoked'][1]);
        self::assertSame(self::TOKEN_HASH, $capturedValues['token_hash'][1]);
        self::assertSame(['id' => [ilDBConstants::T_INTEGER, self::TOKEN_ID]], $capturedWhere);
        self::assertArrayNotHasKey('created_at', $capturedValues);
    }

    /**
     * @param array<string, string>|null $row
     */
    private function statementWithRow(?array $row, int $numRows = 1): ilDBStatement&Stub
    {
        $statement = $this->createStub(ilDBStatement::class);
        $statement->method('numRows')->willReturn($numRows);
        $statement->method('fetchAssoc')->willReturn($row);

        return $statement;
    }
}
