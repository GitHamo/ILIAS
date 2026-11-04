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

namespace ILIAS\ApiGateway\RateLimiting\Domain\Model;

/**
 * Rate limiting should be expressed in seconds internally.
 * Higher-level units (minutes, hours) are policy (business rule), not storage.
 *
 * * policy = business rule, example: "100 requests per minute"
 * * storage = how to measure time internally, example: "Unix timestamps in seconds, windowSeconds = 60"
 *
 * * "Store" time in seconds so system is flexible.
 * * "Express" limits in minutes/hours because humans understand them.
 *
 * Seconds Benefits:
 * - best precision
 * - consistent math
 * - easy composition
 * - future flexibility
 */
final readonly class RateLimit
{
    public function __construct(
        public string $fingerprint, // userId, apiKey, IP, etc.
        public string $action, // endpoint / operation
        public int $windowSeconds, // time window in seconds
        public int $maxUnits, // limit in units (requests, cost, etc.)
    ) {
        if ($windowSeconds <= 0) {
            throw new \InvalidArgumentException('windowSeconds must be positive');
        }

        if ($maxUnits <= 0) {
            throw new \InvalidArgumentException('maxUnits must be positive');
        }

        if ($fingerprint === '') {
            throw new \InvalidArgumentException('fingerprint cannot be empty');
        }

        if ($action === '') {
            throw new \InvalidArgumentException('action cannot be empty');
        }
    }
}
