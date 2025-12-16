<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ApiGateway\Auth\Domain\Model;

use DateTimeImmutable;

/**
 * Represents a refresh token model
 *
 * Some of the fields are immutable, the rest are optional
 */
class RefreshToken
{
    private DateTimeImmutable $createdAt;

    public function __construct(
        private readonly int $userId,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
        private readonly ?int $id = null,
        private bool $isRevoked = false,
        ?DateTimeImmutable $createdAt = null,
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isRevoked(): bool
    {
        return $this->isRevoked;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revoke(): void
    {
        $this->isRevoked = true;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->expiresAt;
    }
}
