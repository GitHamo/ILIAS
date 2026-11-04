<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi;

use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Path;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\SpecMetadata;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\OpenApiRepresentation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiRepresentation::class)]
class OpenApiRepresentationTest extends TestCase
{
    private OpenApiRepresentation $representation;
    private string $baseUrl;
    private string $version;
    private string $title;
    private string $description;
    private string $apiVersion;
    private MockObject&Path $pathMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->representation = new OpenApiRepresentation(
            new SpecMetadata(
                baseUrl: $this->baseUrl = 'foo',
                version: $this->version = 'bar',
                title: $this->title = 'baz',
                description: $this->description = 'qux',
                apiVersion: $this->apiVersion = 'quux',
            ),
            [
                $this->pathMock = $this->createMock(Path::class),
            ],
        );
    }

    public function testHasAccessorToType(): void
    {
        self::assertSame(
            RepresentationType::OpenAPI,
            $this->representation->type(),
        );
    }

    public function testSerializesRepresentation(): void
    {
        $expected = [
            'openapi' => $this->version,
            'info' => [
                'title' => $this->title,
                'description' => $this->description,
                'version' => $this->apiVersion,
            ],
            'servers' => [
                ['url' => $this->baseUrl],
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
            'paths' => [
                $path = 'foo' => $pathData = ['bar' => 'baz'],
            ],
        ];

        $this->pathMock
            ->expects(self::once())
            ->method('getPath')
            ->willReturn($path);

        $this->pathMock
            ->expects(self::once())
            ->method('jsonSerialize')
            ->willReturn($pathData);

        self::assertSame(
            $expected,
            $this->representation->jsonSerialize(),
        );
    }
}
