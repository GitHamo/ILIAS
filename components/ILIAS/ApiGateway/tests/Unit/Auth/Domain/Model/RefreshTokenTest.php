<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Domain\Model;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use ILIAS\ApiGateway\Auth\Domain\Model\RefreshToken;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    private RefreshToken $model;
    private int $userId;
    private string $tokenHash;
    private DateTimeImmutable $expiresAt;
    private int $refreshTokenId;
    private bool $isRevoked;
    private DateTimeImmutable $createdAt;

    #[\Override]
    protected function setUp(): void
    {
        $this->model = new RefreshToken(
            $this->userId = 123,
            $this->tokenHash = 'foo-bar',
            $this->expiresAt = new DateTimeImmutable(),
            $this->refreshTokenId = 321,
            $this->isRevoked = true,
            $this->createdAt = new DateTimeImmutable(),
        );
    }

    public function testHasAccessorToId(): void
    {
        $this->assertSame(
            $this->refreshTokenId,
            $this->model->getId(),
        );
    }

    public function testHasAccessorToUserId(): void
    {
        $this->assertSame(
            $this->userId,
            $this->model->getUserId(),
        );
    }

    public function testHasAccessorToTokenHash(): void
    {
        $this->assertSame(
            $this->tokenHash,
            $this->model->getTokenHash(),
        );
    }

    public function testHasAccessorToExpiresAt(): void
    {
        $this->assertSame(
            $this->expiresAt,
            $this->model->getExpiresAt(),
        );
    }

    public function testHasAccessorToIsRevoked(): void
    {
        $this->assertSame(
            $this->isRevoked,
            $this->model->isRevoked(),
        );
    }

    public function testHasAccessorToCreatedAt(): void
    {
        $this->assertSame(
            $this->createdAt,
            $this->model->getCreatedAt(),
        );
    }

    public function testReturnsIsExpired(): void
    {
        $now = new DateTimeImmutable();
        $expiresAtPast = $now->sub(new DateInterval('PT1H'));
        $expiresAtFuture = $now->add(new DateInterval('PT1H'));

        $actualPast = new RefreshToken(
            $this->userId,
            $this->tokenHash,
            $expiresAtPast,
            $this->refreshTokenId,
            $this->isRevoked,
            $this->createdAt,
        );

        $actualFuture = new RefreshToken(
            $this->userId,
            $this->tokenHash,
            $expiresAtFuture,
            $this->refreshTokenId,
            $this->isRevoked,
            $this->createdAt,
        );

        $this->assertTrue($actualPast->isExpired());
        $this->assertFalse($actualFuture->isExpired());
    }

    public function testCreatesNewInstanceWithDefaultValues(): void
    {
        $now = time();

        $actual = new RefreshToken(
            $this->userId,
            $this->tokenHash,
            $this->expiresAt,
            createdAt: null,
        );

        $this->assertNull($actual->getId());

        $this->assertFalse($actual->isRevoked());

        $this->assertEqualsWithDelta(
            $now,
            $actual->getCreatedAt()->getTimestamp(),
            2.0,
            'The createdAt timestamp should be very close to the time of instantiation.'
        );
    }
}
