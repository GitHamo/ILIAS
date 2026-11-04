<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceContract\Model;

use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use ILIAS\ApiGateway\ServiceContract\Model\Operation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceSpec::class)]
final class ServiceSpecTest extends TestCase
{
    public function testConstructor(): void
    {
        $operation = new Operation(
            method: 'GET',
            path: '/users',
            title: 'Get Users',
            description: 'Returns a list of users',
            requireAuth: false,
            input: null,
            output: null,
        );

        $contract = new ServiceSpec(operations: [$operation]);

        $this->assertCount(1, $contract->operations);
        $this->assertSame($operation, $contract->operations[0]);
    }
}
