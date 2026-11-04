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

use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\SpecMetadata;
use ILIAS\ApiGateway\ServiceContract\Representations\Representation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use Override;

final readonly class OpenApiRepresentation implements Representation
{
    /** @param Model\Path[] $paths */
    public function __construct(
        private SpecMetadata $metadata,
        private array $paths,
    ) {
    }

    #[Override]
    public function type(): RepresentationType
    {
        return RepresentationType::OpenAPI;
    }

    /**
     * @return array<string, array<int|string, array<string, array<string, mixed>|string>|string>|string>
     */
    #[Override]
    public function jsonSerialize(): array
    {
        $paths = [];

        foreach ($this->paths as $path) {
            $paths[$path->getPath()] = $path->jsonSerialize();
        }

        return [
            'openapi' => $this->metadata->version,
            'info' => [
                'title' => $this->metadata->title,
                'description' => $this->metadata->description,
                'version' => $this->metadata->apiVersion,
            ],
            'servers' => [
                ['url' => $this->metadata->baseUrl],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'A valid JWT token is required to access this endpoint.',
                    ],
                ],
                'schemas' => [
                    'Error' => [
                        'type' => 'object',
                        'required' => ['success', 'error'],
                        'properties' => [
                            'success' => [
                                'type' => 'boolean',
                                'default' => false,
                            ],
                            'error' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'InputValidationError' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/Error'],
                            [
                                'type' => 'object',
                                'required' => ['errors'],
                                'properties' => [
                                    'errors' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'paths' => $paths,
        ];
    }
}
