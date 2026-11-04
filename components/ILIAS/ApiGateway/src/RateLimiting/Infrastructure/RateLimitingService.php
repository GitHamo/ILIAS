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

namespace ILIAS\ApiGateway\RateLimiting\Infrastructure;

use DateTimeImmutable;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimitRepository;
use Override;

use function sprintf;

final readonly class RateLimitingService implements RateLimiting
{
    public function __construct(
        private RateLimitRepository $repository,
    ) {
    }

    #[Override]
    public function consume(
        RateLimit $limit,
        int $units,
        DateTimeImmutable $now,
    ): bool {
        $since = $now->modify(sprintf('-%d seconds', $limit->windowSeconds));
        $used = $this->repository->sumUnits(
            $limit->fingerprint,
            $limit->action,
            $since,
        );

        if ($used + $units > $limit->maxUnits) {
            return false;
        }

        $this->repository->store(
            new RateLimitEntry(
                $limit->fingerprint,
                $limit->action,
                $units,
                $now,
            )
        );

        return true;
    }

    #[Override]
    public function purgeExpired(DateTimeImmutable $now): int
    {
        return $this->repository->deleteExpired($now);
    }
}
