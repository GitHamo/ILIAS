<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Infrastructure;

use ilDBConstants;
use ilDBStatement;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseUserRepository;
use ILIAS\Database\Connection;
use ILIAS\HTTP\StatusCode;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * login() is not covered: it builds ILIAS legacy auth objects internally.
 */
#[CoversClass(DatabaseUserRepository::class)]
class DatabaseUserRepositoryTest extends TestCase
{
    private const int USER_ID = 42;

    private Connection&Stub $connection;
    private DatabaseUserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createStub(Connection::class);
        $this->repository = new DatabaseUserRepository($this->connection);
    }

    public function testReturnsExistingUser(): void
    {
        // The database driver returns every column as string.
        $this->connection->method('queryF')->willReturn($this->statementWithRow(['usr_id' => (string) self::USER_ID]));

        $user = $this->repository->getById(self::USER_ID);

        self::assertSame(self::USER_ID, $user->getId());
    }

    public function testRejectsUnknownUser(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow(null));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(StatusCode::HTTP_NOT_FOUND);

        $this->repository->getById(self::USER_ID);
    }

    public function testRejectsEmptyRow(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(StatusCode::HTTP_NOT_FOUND);

        $this->repository->getById(self::USER_ID);
    }

    public function testRejectsRowOfAnotherUser(): void
    {
        $this->connection->method('queryF')->willReturn($this->statementWithRow(['usr_id' => '43']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(StatusCode::HTTP_NOT_FOUND);

        $this->repository->getById(self::USER_ID);
    }

    public function testLooksUpOnlyTheRequestedUserId(): void
    {
        $captured = ['types' => [], 'values' => []];
        $this->connection->method('queryF')->willReturnCallback(
            function (string $query, array $types, array $values) use (&$captured): ilDBStatement {
                $captured = ['types' => $types, 'values' => $values];

                return $this->statementWithRow(['usr_id' => (string) self::USER_ID]);
            }
        );

        $this->repository->getById(self::USER_ID);

        self::assertSame([ilDBConstants::T_INTEGER], $captured['types']);
        self::assertSame([self::USER_ID], $captured['values']);
    }

    /**
     * @param array<string, string>|null $row
     */
    private function statementWithRow(?array $row): ilDBStatement&Stub
    {
        $statement = $this->createStub(ilDBStatement::class);
        $statement->method('fetchAssoc')->willReturn($row);

        return $statement;
    }
}
