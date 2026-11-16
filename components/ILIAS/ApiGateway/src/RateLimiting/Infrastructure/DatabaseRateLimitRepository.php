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
use ilDBConstants;
use ILIAS\ApiGateway\RateLimiting\Domain\Model\RateLimitEntry;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimitRepository;
use ILIAS\Database\Connection;
use Override;

/**
 * Unit tested with a stubbed Connection: no integration suite with a real
 * database exists. Checks row mapping and bound values, not the SQL itself.
 */
final readonly class DatabaseRateLimitRepository implements RateLimitRepository
{
    private const string TABLE_NAME = 'apig_rate_limits';
    private const string TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private Connection $database,
    ) {
    }

    #[Override]
    public function sumUnits(
        string $fingerprint,
        string $scope,
        DateTimeImmutable $since,
    ): int {
        $res = $this->database->queryF(
            'SELECT COALESCE(SUM(units), 0) AS total
               FROM ' . self::TABLE_NAME . '
              WHERE fingerprint = %s
                AND scope   = %s
                AND created_at >= %s',
            [
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TIMESTAMP,
            ],
            [$fingerprint, $scope, $since->format(self::TIMESTAMP_FORMAT)]
        );

        $row = $this->database->fetchAssoc($res);

        return (int) ($row['total'] ?? 0);
    }

    #[Override]
    public function store(RateLimitEntry $entry): void
    {
        $this->database->manipulateF(
            'INSERT INTO ' . self::TABLE_NAME . '
                (id, fingerprint, scope, units, created_at)
             VALUES (%s, %s, %s, %s, %s)',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_TEXT,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_TIMESTAMP,
            ],
            [
                $this->database->nextId(self::TABLE_NAME),
                $entry->fingerprint,
                $entry->scope,
                $entry->units,
                $entry->createdAt->format(self::TIMESTAMP_FORMAT),
            ]
        );
    }

    #[Override]
    public function deleteExpired(DateTimeImmutable $now): int
    {
        $count = $this->database->manipulateF(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE created_at <= %s',
            [ilDBConstants::T_TIMESTAMP],
            [$now->format(self::TIMESTAMP_FORMAT)],
        );

        return $count;
    }
}
