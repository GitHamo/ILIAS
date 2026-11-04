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
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;

interface RateLimitRepository
{
    /**
     * @param string $fingerprint unique identifier, e.g., userId, apiKey, IP, etc.
     * @param string $scope endpoint / operation
     * @param DateTimeImmutable $since from which to sum units
     *
     * @return int The sum of all units in the sliding window
     */
    public function sumUnits(
        string $fingerprint,
        string $scope,
        DateTimeImmutable $since,
    ): int;

    /**
     * Persist a single rate limit entry.
     */
    public function store(RateLimitEntry $entry): void;

    /**
     * Removes expired rate limits (deletes any rate limit at or before the given moment)
     *
     * @return int The number of deleted rate limits
     */
    public function deleteExpired(DateTimeImmutable $now): int;
}
