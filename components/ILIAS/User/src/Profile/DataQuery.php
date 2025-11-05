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

namespace ILIAS\User\Profile;

use ILIAS\User\Profile\Fields\Field;

class DataQuery
{
    private const string USER_TABLE = 'usr_data';
    private const string MULTI_DATA_TABLE = 'usr_profile_data';
    private const string ARRAY_SEPARATOR = '##!:!##';
    private const string RECORDS_QUERY_INIT = 'SELECT ';
    private const string CNT_QUERY_INIT = 'SELECT COUNT(DISTINCT ' . self::USER_TABLE . '.usr_id) cnt FROM ' . self::USER_TABLE;

    private array $multi_fields = [];
    private array $select_fields;
    private array $udf_fields = [];
    private array $join = [];
    private array $where = [];
    private string $order = '';
    private bool $multi_data_table_joined = false;
    private bool $additional_fields_processed = false;

    public function __construct(
        private readonly \ilDBInterface $db,
        array $select_fields
    ) {
        $this->select_fields = array_map(
            fn(string $v): string => self::USER_TABLE . '.' . $v,
            $select_fields
        );
    }

    public function withAdditionalAdditionalTableSelectField(string $field_name): self
    {
        $clone = clone $this;
        $clone->select_fields[] = $field_name;
        return $clone;
    }

    public function withAdditionalDefaultTableSelectField(string $field_name): self
    {
        $clone = clone $this;
        $clone->select_fields[] = self::USER_TABLE . '.' . $field_name;
        return $clone;
    }

    public function withAdditionalUdfField(Field $field): self
    {
        $clone = clone $this;
        $clone->udf_fields[] = $field;
        return $clone;
    }

    public function withAdditionalMultiField(string $field_name): self
    {
        $clone = clone $this;
        $clone->multi_fields[] = $field_name;
        return $clone;
    }

    public function withAdditionalJoin(string $join): self
    {
        $clone = clone $this;
        $clone->join[] = $join;
        return $clone;
    }

    public function withAdditionalWhere(string $where): self
    {
        $clone = clone $this;
        $clone->where[] = $where;
        return $clone;
    }

    public function withAdditionalMultiDataWhere(string $identifier, string $value): self
    {
        $clone = clone $this;
        $clone->where[] = self::MULTI_DATA_TABLE . ".field_id = {$identifier} AND "
            . self::MULTI_DATA_TABLE . ".value = {$value}";
        return $clone;
    }

    public function withAdditionalTableOrder(string $order): self
    {
        $clone = clone $this;
        $clone->order = $order;
        return $clone;
    }

    public function withMultiDataTableOrder(string $order_field, string $direction): self
    {
        $clone = clone $this;
        $clone->order = "ORDER BY `{$order_field}` {$direction}";
        return $clone;
    }

    public function withDefaultTableOrderFields(array $order_fields, string $direction): self
    {
        $clone = clone $this;
        $clone->order = 'ORDER BY ' . implode(', ', array_reduce(
            $order_fields,
            static function (array $c, string $v) use ($direction): array {
                $c[] = self::USER_TABLE . ".`{$v}` {$direction}";
                return $c;
            },
            []
        ));
        return $clone;
    }

    public function withLimitedUsers(array $users): self
    {
        if ($users === []) {
            return $this;
        }
        $clone = clone $this;
        $clone->withAdditionalWhere(
            $this->db->in('usr_data.usr_id', $users, false, \ilDBConstants::T_INTEGER)
        );
        return $clone;
    }

    public function withJoinedMultiDataTable(): self
    {
        $clone = clone $this;
        $clone->joins[] = $this->buildJoinForMultiDataTable();
        $clone->multi_data_table_joined = true;
        return $clone;
    }

    public function retrieveRecords(): array
    {
        $this->addAdditionalSelectAndJoinForUdfAndMultiValueFields();
        $statement = $this->db->query(
            self::RECORDS_QUERY_INIT . implode(', ', $this->select_fields) . PHP_EOL
            . 'FROM usr_data' . PHP_EOL
            . implode(PHP_EOL, $this->join) . PHP_EOL
            . 'WHERE usr_data.usr_id <> ' . $this->db->quote(ANONYMOUS_USER_ID, \ilDBConstants::T_INTEGER) . PHP_EOL . 'AND '
            . implode(PHP_EOL . 'AND ', $this->where) . PHP_EOL
            . 'GROUP BY ' . self::USER_TABLE . '.usr_id' . PHP_EOL
            . $this->order
        );

        $result = [];
        while (($row = $this->db->fetchAssoc($statement)) !== null) {
            $row['usr_id'] = (int) $row['usr_id'];
            $result[] = $this->explodeArrayValues($row);
        }
        return $result;
    }

    public function getCnt(): int
    {
        $this->addAdditionalSelectAndJoinForUdfAndMultiValueFields();
        return $this->db->fetchObject(
            $this->db->query(
                self::CNT_QUERY_INIT . PHP_EOL
                . implode(PHP_EOL, $this->join) . PHP_EOL
                . 'WHERE usr_data.usr_id <> ' . $this->db->quote(ANONYMOUS_USER_ID, \ilDBConstants::T_INTEGER) . PHP_EOL . 'AND '
                . implode(PHP_EOL . 'AND ', $this->where)
            )
        )->cnt ?? 0;
    }

    private function addAdditionalSelectAndJoinForUdfAndMultiValueFields(): void
    {
        if ($this->additional_fields_processed
            || $this->multi_fields === [] && $this->udf_fields === []) {
            return;
        }

        if (!$this->multi_data_table_joined) {
            $this->join[] = $this->buildJoinForMultiDataTable();
            $this->multi_data_table_joined = true;
        }

        foreach ($this->multi_fields as $field) {
            $this->select_fields[] = 'GROUP_CONCAT(DISTINCT IF(' . self::MULTI_DATA_TABLE
                . ".field_id = {$this->db->quote($field, \ilDBConstants::T_TEXT)}, "
                . self::MULTI_DATA_TABLE . '.value, NULL) '
                . "SEPARATOR '" . self::ARRAY_SEPARATOR . "') `{$field}`";
        }

        foreach ($this->udf_fields as $field) {
            $this->select_fields[] = 'GROUP_CONCAT(DISTINCT IF(' . self::MULTI_DATA_TABLE
                . ".field_id = {$this->db->quote($field->getIdentifier(), \ilDBConstants::T_TEXT)}, "
                . self::MULTI_DATA_TABLE . '.value, NULL) '
                . "SEPARATOR '" . self::ARRAY_SEPARATOR . "') `udf_{$field->getIdentifier()}`";
        }

        $this->additional_fields_processed = true;
    }

    private function buildJoinForMultiDataTable(): string
    {
        return 'LEFT JOIN ' . self::MULTI_DATA_TABLE . ' ON '
            . self::MULTI_DATA_TABLE . '.usr_id = ' . self::USER_TABLE . '.usr_id';
    }

    private function explodeArrayValues(array $row): array
    {
        return array_map(
            static function (mixed $v): mixed {
                if (!is_string($v) || mb_stristr($v, self::ARRAY_SEPARATOR) === false) {
                    return $v;
                }

                return explode(self::ARRAY_SEPARATOR, $v);
            },
            $row
        );
    }
}
