<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\SchemaType;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\OpenApiSchemaSerializer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenApiSchemaSerializer::class)]
class OpenApiSchemaSerializerTest extends TestCase
{
    private OpenApiSchemaSerializer $serializer;

    #[\Override]
    protected function setUp(): void
    {
        $this->serializer = new OpenApiSchemaSerializer();
    }

    #[DataProvider('supportedTypesProvider')]
    public function testSerializesSchemaWithType(SchemaType $type, string $expected): void
    {
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => $type,
        ]);

        $actual = $this->serializer->serialize($schema);

        self::assertArrayHasKey('type', $actual);
        self::assertSame($expected, $actual['type']);
    }

    /**
     * @return array<string, array{SchemaType, string}>
     */
    public static function supportedTypesProvider(): array
    {
        return [
            'array' => [SchemaType::ARRAY, 'array'],
            'boolean' => [SchemaType::BOOL, 'boolean'],
            'integer' => [SchemaType::INT, 'integer'],
            'number' => [SchemaType::NUM, 'number'],
            'object' => [SchemaType::OBJECT, 'object'],
            'string' => [SchemaType::STRING, 'string'],
        ];
    }

    public function testSerializesSchemaWithTitle(): void
    {
        $title = 'Test Title';
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => SchemaType::STRING,
            'getTitle' => $title,
        ]);

        $actual = $this->serializer->serialize($schema);

        self::assertArrayHasKey('title', $actual);
        self::assertSame($title, $actual['title']);
    }

    public function testSerializesSchemaWithDescription(): void
    {
        $description = 'Test Description';
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => SchemaType::STRING,
            'getDescription' => $description,
        ]);

        $actual = $this->serializer->serialize($schema);

        self::assertArrayHasKey('description', $actual);
        self::assertSame($description, $actual['description']);
    }

    public function testSerializesSchemaWithTitleAndDescription(): void
    {
        $title = 'Test Title';
        $description = 'Test Description';
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => SchemaType::STRING,
            'getTitle' => $title,
            'getDescription' => $description,
        ]);

        $actual = $this->serializer->serialize($schema);

        self::assertSame($title, $actual['title']);
        self::assertSame($description, $actual['description']);
    }

    public function testSerializesSchemaWithoutOptionalParameters(): void
    {
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => SchemaType::STRING,
            'getTitle' => '',
            'getDescription' => null,
        ]);

        $actual = $this->serializer->serialize($schema);

        self::assertArrayNotHasKey('title', $actual);
        self::assertArrayNotHasKey('description', $actual);
    }

    public function testThrowsExceptionForUnsupportedType(): void
    {
        $schema = $this->createConfiguredMock(Schema::class, [
            'getType' => SchemaType::REF,
        ]);

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Unsupported type: reference');

        $this->serializer->serialize($schema);
    }
}
