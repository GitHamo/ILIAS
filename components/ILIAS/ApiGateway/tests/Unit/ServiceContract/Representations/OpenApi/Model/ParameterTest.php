<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi\Model;

use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\SchemaType;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Parameter;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\ParameterLocation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parameter::class)]
final class ParameterTest extends TestCase
{
    public function testSerializesParameterIntoArray(): void
    {
        $parameter = new Parameter(
            $name = 'foo',
            $location = ParameterLocation::PATH,
            $description = 'bar',
            $required = true,
            $this->createConfiguredMock(Schema::class, [
                'getType' => $type = SchemaType::STRING,
            ]),
        );

        $expected = [
            'name' => $name,
            'in' => $location->value,
            'required' => $required,
            'description' => $description,
            'schema' => [
                'type' => $type->value,
            ],
        ];

        $actual = $parameter->jsonSerialize();

        self::assertSame($expected, $actual);
    }
}
