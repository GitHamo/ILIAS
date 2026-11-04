<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Model;

use ILIAS\ApiGateway\ServiceContract\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Operation::class)]
final class OperationTest extends TestCase
{
    public function testConstructor(): void
    {
        $inputSchema = null;  // replace with a Schema instance or mock if needed
        $outputSchema = null;

        $operation = new Operation(
            method: 'POST',
            path: '/users',
            title: 'Create User',
            description: 'Creates a new user',
            requireAuth: true,
            input: $inputSchema,
            output: $outputSchema
        );

        $this->assertSame('POST', $operation->method);
        $this->assertSame('/users', $operation->path);
        $this->assertSame('Create User', $operation->title);
        $this->assertSame('Creates a new user', $operation->description);
        $this->assertTrue($operation->requireAuth);
        $this->assertSame($inputSchema, $operation->input);
        $this->assertSame($outputSchema, $operation->output);
    }
}
