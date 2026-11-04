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

namespace ILIAS\ApiGateway\RateLimiting\Domain;

use DateTimeImmutable;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimit;

interface RateLimiting
{
    /**
     * @param int $units The number of units to consume
     *
     * @return bool True if the rate limit allows the action, false if the rate limit is exceeded
     */
    public function consume(
        RateLimit $limit,
        int $units,
        DateTimeImmutable $now,
    ): bool;

    /**
     * Removes expired rate limits (deletes any rate limit before the given moment)
     *
     * @return int The number of deleted rate limits
     */
    public function purgeExpired(DateTimeImmutable $now): int;
}
