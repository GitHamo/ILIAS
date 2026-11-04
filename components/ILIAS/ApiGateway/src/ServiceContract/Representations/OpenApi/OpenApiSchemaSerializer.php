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

namespace ILIAS\ApiGateway\ServiceContract\Representations\OpenApi;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\SchemaType;
use ILIAS\ApiGateway\ServiceContract\Representations\SchemaSerializer;
use InvalidArgumentException;

/**
 * Transitional implementation for Phase 3.
 * Note: Complex types (ARRAY, OBJECT) will produce invalid OpenAPI until Phase 4,
 * as they currently lack mandatory properties like 'items' or 'properties'.
 */
final readonly class OpenApiSchemaSerializer implements SchemaSerializer
{
    #[\Override]
    public function serialize(Schema $schema): array
    {
        $type = $schema->getType();

        $serialized = [
            'type' => match ($type) {
                SchemaType::ARRAY,
                SchemaType::BOOL,
                SchemaType::INT,
                SchemaType::NUM,
                SchemaType::OBJECT,
                SchemaType::STRING => $type->value,
                default => throw new InvalidArgumentException("Unsupported type: {$type->value}"),
            },
        ];

        $description = $schema->getDescription();

        if ($description !== null && $description !== '') {
            $serialized['description'] = $description;
        }

        $title = $schema->getTitle();

        if ($title !== null && $title !== '') {
            $serialized['title'] = $title;
        }

        return $serialized;
    }
}
