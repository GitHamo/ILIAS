<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Factories\ServiceSpecFactory;
use ILIAS\ApiGateway\Application\Middlewares\AuthenticationMiddleware;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Routing\HttpMethod;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\ServiceContract\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Model\Schema;
use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServiceSpecFactory::class)]
class ServiceSpecFactoryTest extends TestCase
{
    public function testCreatesInstanceOfServiceSpecFromRoutes(): void
    {
        $inputSchema = $this->createMock(Schema::class);

        $routes = [
            $this->createConfiguredMock(Route::class, [
                'getName' => $name1 = 'test-name-1',
                'getDescription' => $description1 = 'test-description-1',
                'getPath' => $path1 = '/foo',
                'getMethod' => $method1 = HttpMethod::GET,
                'getAction' => $this->createMock(Action::class),
                'getInput' => $inputSchema, // This will be nullified by the factory for GET
                'getOutput' => $output1 = $this->createMock(Schema::class),
                'getMiddlewares' => [],
            ]),
            $this->createConfiguredMock(Route::class, [
                'getName' => $name2 = 'test-name-2',
                'getDescription' => $description2 = 'test-description-2',
                'getPath' => $path2 = '/foo/bar',
                'getMethod' => $method2 = HttpMethod::POST,
                'getAction' => $this->createMock(Action::class),
                'getInput' => $inputSchema,
                'getOutput' => $output2 = $this->createMock(Schema::class),
                'getMiddlewares' => [AuthenticationMiddleware::class],
            ]),
        ];

        $operations = [
            new Operation(
                $method1->value,
                $path1,
                $name1,
                $description1,
                false,
                null, // Input should be null for GET
                $output1,
            ),
            new Operation(
                $method2->value,
                $path2,
                $name2,
                $description2,
                true,
                $inputSchema, // Input should be the schema for POST
                $output2,
            ),
        ];

        $expected = new ServiceSpec($operations);

        $actual = (new ServiceSpecFactory())->createFromRoutes(...$routes);

        self::assertEquals($expected, $actual);
    }
}
