<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Representations\OpenApi\Model;

use ILIAS\ApiGateway\ServiceContract\Model\Operation as ServiceContractOperation;
use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Representations\OpenApi\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Representations\SchemaSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Operation::class)]
final class OperationTest extends TestCase
{
    private MockObject&SchemaSerializer $schemaSerializerMock;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaSerializerMock = $this->createMock(SchemaSerializer::class);
    }

    public function testHasAccessorToMethod(): void
    {
        $contractOperation = new ServiceContractOperation(
            $method = 'GET',
            '/foo',
            'bar',
            'baz',
            false,
            null,
            null,
        );

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock);

        self::assertSame(
            $method,
            $actual->getMethod(),
        );
    }

    public function testSerializesOperation(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            $operationId = 'operation-test-id',
            $operationSummary = 'operation-test-summary',
            false,
            $this->createConfiguredMock(Schema::class, [
                'getDescription' => $description = 'Request body test description',
            ]),
            $this->createMock(Schema::class),
        );

        $expected = [
            'summary' => $operationSummary,
            'operationId' => $operationId,
            'requestBody' => [
                'description' => $description,
                'content' => [
                    'application/json' => [
                        'schema' => $inputSchema = [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['success', 'data'],
                                'properties' => [
                                    'success' => [
                                        'type' => 'boolean',
                                        'default' => true,
                                    ],
                                    'data' => $outputSchema = [
                                        'foo' => 'baz',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '500' => [
                    'description' => 'Unexpected error',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                '422' => [
                    'description' => 'Unprocessable Entity - Invalid errors in the request (e.g., input validation errors).',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/InputValidationError',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->schemaSerializerMock->expects(self::exactly(2))
            ->method('serialize')
            ->with(
                self::isInstanceOf(Schema::class),
            )
            ->willReturnOnConsecutiveCalls($inputSchema, $outputSchema);

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertSame($expected, $actual);
    }

    public function testSerializesOperationWithoutOptionalParameters(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            'bar',
            'baz',
            false,
            null,
            null,
        );

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertEmpty(
            $actual['parameters'],
        );

        self::assertArrayNotHasKey(
            'requestBody',
            $actual,
        );

        $responses = $actual['responses'];
        self::assertIsArray($responses);

        self::assertArrayHasKey('200', $responses);
        self::assertArrayHasKey('500', $responses);
        self::assertArrayNotHasKey('401', $responses);
        self::assertArrayNotHasKey('422', $responses);

        $successResponse = $responses['200'];
        self::assertIsArray($successResponse);

        self::assertArrayHasKey('content', $successResponse);
        self::assertArrayHasKey('application/json', $successResponse['content']);
        self::assertArrayHasKey('schema', $successResponse['content']['application/json']);
        self::assertArrayHasKey('properties', $successResponse['content']['application/json']['schema']);
        self::assertArrayHasKey('data', $successResponse['content']['application/json']['schema']['properties']);
        self::assertEmpty($successResponse['content']['application/json']['schema']['properties']['data']);
    }

    public function testSerializesOperationWithNullInputSchemaDescription(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            'bar',
            'baz',
            false,
            $this->createConfiguredMock(Schema::class, [
                'getDescription' => null,
            ]),
            null,
        );

        $this->schemaSerializerMock->expects(self::once())
            ->method('serialize')
            ->willReturn([]);

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertArrayHasKey('requestBody', $actual);
        self::assertSame('Request body', $actual['requestBody']['description']);
    }

    public function testSerializesOperationWithInputSchemaIncludes422Response(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            'bar',
            'baz',
            false,
            $this->createMock(Schema::class),
            null,
        );

        $this->schemaSerializerMock->expects(self::once())
            ->method('serialize')
            ->willReturn([]);

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertArrayHasKey('responses', $actual);
        self::assertArrayHasKey('422', $actual['responses']);
        self::assertSame(
            'Unprocessable Entity - Invalid errors in the request (e.g., input validation errors).',
            $actual['responses']['422']['description']
        );
    }

    public function testSerializesOperationIncludes500Response(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            'bar',
            'baz',
            false,
            null,
            null,
        );

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertArrayHasKey('responses', $actual);
        self::assertArrayHasKey('500', $actual['responses']);
        self::assertSame(
            'Unexpected error',
            $actual['responses']['500']['description']
        );
    }

    public function testSerializesSecureOperation(): void
    {
        $contractOperation = new ServiceContractOperation(
            'GET',
            '/foo',
            'bar',
            'baz',
            true,
            null,
            null,
        );

        $actual = Operation::create($contractOperation, $this->schemaSerializerMock)->jsonSerialize();

        self::assertArrayHasKey('security', $actual);
        self::assertSame([['bearerAuth' => []]], $actual['security']);
    }
}
