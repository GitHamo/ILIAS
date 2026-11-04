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

namespace ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use JsonSerializable;

readonly class Parameter implements JsonSerializable
{
    public function __construct(
        private string $name,
        private ParameterLocation $in,
        private string $description,
        private bool $required,
        private Schema $schema,
    ) {
    }

    /**
     * @return array<string, string|bool|array<string, mixed>>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'in' => $this->in->value,
            'required' => $this->required,
            'description' => $this->description,
            'schema' => [
                'type' => $this->schema->getType()->value, // @todo: serialize schema
            ],
        ];
    }
}
