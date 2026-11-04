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

use JsonSerializable;

readonly class Path implements JsonSerializable
{
    /** @param Operation[] $operations */
    public function __construct(
        private string $path,
        private array $operations,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        $callback = function (array $result, Operation $operation): array {
            $result[strtolower($operation->getMethod())] = $operation->jsonSerialize();

            return $result;
        };

        return array_reduce($this->operations, $callback, []);
    }
}
