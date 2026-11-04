<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi;

use ILIAS\ApiGateway\ServiceContract\Model\Operation as ServiceContractOperation;
use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Path;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\SpecMetadata;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\OpenApiGenerator;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\OpenApiRepresentation;
use ILIAS\ApiGateway\ServiceContract\Representations\RepresentationType;
use ILIAS\ApiGateway\ServiceContract\Representations\SchemaSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiGenerator::class)]
class OpenApiGeneratorTest extends TestCase
{
    private MockObject&SchemaSerializer $schemaSerializer;
    private SpecMetadata $specMetadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->schemaSerializer = $this->createMock(SchemaSerializer::class);
        $this->specMetadata = new SpecMetadata(
            'https://ilias.de',
            'foo',
            'bar',
            'baz',
            'qux',
        );
    }

    #[DataProvider('representationTypeProvider')]
    public function testSupportsOnlyOpenAPI(RepresentationType $type, bool $expected): void
    {
        $generator = new OpenApiGenerator(
            $this->specMetadata,
            $this->schemaSerializer,
        );

        $actual = $generator->supports($type);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{RepresentationType, bool}>
     */
    public static function representationTypeProvider(): array
    {
        return [
            'OpenAPI is supported' => [RepresentationType::OpenAPI, true],
            'GraphQL is not supported' => [RepresentationType::GraphQL, false],
            'gRPC is not supported' => [RepresentationType::gRPC, false],
            'SOAP is not supported' => [RepresentationType::SOAP, false],
        ];
    }

    public function testGeneratesRepresentation(): void
    {
        $generator = new OpenApiGenerator(
            $this->specMetadata,
            $this->schemaSerializer,
        );

        $spec = new ServiceSpec([
            $operation = new ServiceContractOperation(
                'quux',
                'corge',
                'grault',
                'garply',
                false,
                null,
                $this->createMock(Schema::class),
            ),
        ]);

        $path = new Path(
            'corge',
            [
                Operation::create($operation, $this->schemaSerializer),
            ]
        );

        $expected = new OpenApiRepresentation($this->specMetadata, [$path]);

        $actual = $generator->generate($spec);

        self::assertEquals($expected, $actual);
    }
}
