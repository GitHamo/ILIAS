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

namespace ILIAS\ApiGateway\Setup\Steps;

use ilDatabaseUpdateSteps;
use ilDBInterface;
use ilDBPdo;

class ApiGatewayDBUpdateSteps implements ilDatabaseUpdateSteps
{
    private const string TABLE_PREFIX = 'apig_';
    private const string TABLE_NAME_REFRESH_TOKENS = self::TABLE_PREFIX . 'refresh_tokens';
    private const string TABLE_NAME_RATE_LIMITS = self::TABLE_PREFIX . 'rate_limits';
    /** @var ilDBPdo */
    protected ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    public function step_1(): void
    {
        if ($this->db->tableExists(self::TABLE_NAME_REFRESH_TOKENS)) {
            return;
        }

        $fields = [
            'id' => [
                'type' => 'integer',
                'length' => 4,
                'notnull' => true,
                'default' => 0,
            ],
            'user_id' => [
                'type' => 'integer',
                'length' => 8,
                'notnull' => true,
                'default' => 0,
            ],
            'token_hash' => [
                'type' => 'text',
                'length' => 255,
                'notnull' => true,
            ],
            'is_revoked' => [
                'type' => 'integer',
                'length' => 1,
                'notnull' => true,
                'default' => 0,
            ],
            'expires_at' => [
                'type' => 'timestamp',
                'notnull' => true,
                'default' => '',
            ],
            'created_at' => [
                'type' => 'timestamp',
                'notnull' => true,
                'default' => '',
            ],
            'updated_at' => [
                'type' => 'timestamp',
                'notnull' => true,
                'default' => '',
            ],
        ];

        $this->db->createTable(self::TABLE_NAME_REFRESH_TOKENS, $fields);
        $this->db->addPrimaryKey(self::TABLE_NAME_REFRESH_TOKENS, ['id']);
        $this->db->createSequence(self::TABLE_NAME_REFRESH_TOKENS);
        $this->db->addUniqueConstraint(self::TABLE_NAME_REFRESH_TOKENS, ['token_hash'], 'th1');
        $this->db->addIndex(self::TABLE_NAME_REFRESH_TOKENS, ['user_id'], 'usr');
        $this->db->addIndex(self::TABLE_NAME_REFRESH_TOKENS, ['expires_at'], 'exp');
    }

    public function step_2(): void
    {
        if ($this->db->tableExists(self::TABLE_NAME_RATE_LIMITS)) {
            return;
        }

        $fields = [
            'id' => [
                'type' => 'integer',
                'length' => 4,
                'notnull' => true,
                'default' => 0,
            ],
            'fingerprint' => [
                'type' => 'text',
                'length' => 255,
                'notnull' => true,
            ],
            'scope' => [
                'type' => 'text',
                'length' => 255,
                'notnull' => true,
            ],
            'units' => [
                'type' => 'integer',
                'length' => 4,
                'notnull' => true,
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'timestamp',
                'notnull' => true,
                'default' => '',
            ],
        ];

        $this->db->createTable(self::TABLE_NAME_RATE_LIMITS, $fields);
        $this->db->addPrimaryKey(self::TABLE_NAME_RATE_LIMITS, ['id']);
        $this->db->createSequence(self::TABLE_NAME_RATE_LIMITS);
    }

    public function step_3(): void
    {
        $fsc_idx_fields = [
            'fingerprint',
            'scope',
            'created_at',
        ];
        $ca_idx_fields = [
            'created_at',
        ];
        if (!$this->db->indexExistsByFields(self::TABLE_NAME_RATE_LIMITS, $fsc_idx_fields)) {
            // Optimizes the sumUnits query.
            $this->db->addIndex(
                self::TABLE_NAME_RATE_LIMITS,
                $fsc_idx_fields,
                'fsc'
            );
        }

        if (!$this->db->indexExistsByFields(self::TABLE_NAME_RATE_LIMITS, $ca_idx_fields)) {
            // Crucial optimization for the deleteExpired query.
            $this->db->addIndex(
                self::TABLE_NAME_RATE_LIMITS,
                $ca_idx_fields,
                'ca'
            );
        }
    }
}
