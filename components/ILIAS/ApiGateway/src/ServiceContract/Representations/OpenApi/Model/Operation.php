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

use ILIAS\ApiGateway\ServiceContract\Model\Operation as ServiceContractOperation;
use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Representations\SchemaSerializer;
use JsonSerializable;
use Override;

readonly class Operation implements JsonSerializable
{
    private const string CONTENT_TYPE = 'application/json';

    /**
     * @param Parameter[] $parameters
     */
    private function __construct(
        private string $method,
        private string $id,
        private string $summary,
        private array $parameters,
        private SchemaSerializer $schemaSerializer,
        private bool $requireAuth,
        private ?Schema $input,
        private ?Schema $output,
    ) {
    }

    public static function create(
        ServiceContractOperation $contractOperation,
        SchemaSerializer $schemaSerializer,
    ): self {
        return new self(
            $contractOperation->method,
            $contractOperation->title,
            $contractOperation->description,
            [], // @todo: collect parameters
            $schemaSerializer,
            $contractOperation->requireAuth,
            $contractOperation->input,
            $contractOperation->output,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function jsonSerialize(): mixed
    {
        $operation = [
            'summary' => $this->summary,
            'operationId' => $this->id,
        ];

        if (!empty($this->parameters)) {
            $operation['parameters'] = $this->serializeParameters();
        }

        $requestBody = $this->getRequestBody();

        if ($requestBody !== null) {
            $operation['requestBody'] = $requestBody;
        }

        $operation['responses'] = $this->getResponses();

        if ($this->requireAuth) {
            $operation['security'] = [
                ['bearerAuth' => []],
            ];
        }

        return $operation;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeParameters(): array
    {
        return array_map(
            fn(Parameter $parameter): array => $parameter->jsonSerialize(),
            $this->parameters
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRequestBody(): ?array
    {
        if (!$this->input instanceof Schema) {
            return null;
        }

        return [
            'description' => $this->input->getDescription() ?? 'Request body',
            'content' => [
                self::CONTENT_TYPE => [
                    'schema' => $this->serializeSchema($this->input),
                ],
            ],
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function getResponses(): array
    {
        $responses = [
            '200' => $this->getSuccessResponse(),
            '500' => $this->getErrorResponse('Unexpected error', 'Error'),
        ];

        if ($this->requireAuth) {
            $responses['401'] = $this->getErrorResponse(
                'Unauthorized - Authentication is required and has failed or has not yet been provided.',
                'Error'
            );
        }

        if ($this->input instanceof Schema) {
            $responses['422'] = $this->getErrorResponse(
                'Unprocessable Entity - Invalid errors in the request (e.g., input validation errors).',
                'InputValidationError'
            );
        }

        return $responses;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSuccessResponse(): array
    {
        return [
            'description' => 'Successful response',
            'content' => [
                self::CONTENT_TYPE => [
                    'schema' => [
                        'type' => 'object',
                        'required' => ['success', 'data'],
                        'properties' => [
                            'success' => [
                                'type' => 'boolean',
                                'default' => true,
                            ],
                            'data' => $this->serializeSchema($this->output),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getErrorResponse(string $description, string $ref): array
    {
        return [
            'description' => $description,
            'content' => [
                self::CONTENT_TYPE => [
                    'schema' => [
                        '$ref' => '#/components/schemas/' . $ref,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSchema(?Schema $schema): array
    {
        if ($schema === null) {
            return [];
        }

        return $this->schemaSerializer->serialize($schema);
    }
}
