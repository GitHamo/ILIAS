<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use Closure;
use ILIAS\ApiGateway\Routing\ApiAction;
use ILIAS\ApiGateway\Routing\RouteHandler;
use Override;
use PHPUnit\Framework\TestCase;

final class ApiActionTest extends TestCase
{
    private ApiAction $apiAction;
    private string $name = 'foo';
    private string $path = '/test';
    private string $method = 'testMethod';
    private string $description = 'A test API action';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiAction = new ApiAction(
            $this->name,
            $this->path,
            [$this->method],
            $this->description,
            fn(): string => 'handled',
        );
    }

    public function testHasAccessorToName(): void
    {
        self::assertSame(
            $this->name,
            $this->apiAction->getName(),
        );
    }

    public function testHasAccessorToPath(): void
    {
        self::assertSame(
            $this->path,
            $this->apiAction->getPath(),
        );
    }

    public function testHasAccessorToMethods(): void
    {
        self::assertSame(
            [$this->method],
            $this->apiAction->getMethods(),
        );
    }

    public function testHasAccessorToDescription(): void
    {
        self::assertSame(
            $this->description,
            $this->apiAction->getDescription(),
        );
    }

    /**
     * @param string[] $methods
     * @dataProvider edgeCasesDataProvider
     */
    public function testHandlesEdgeCases(
        string $name,
        string $path,
        array $methods,
        string $description,
    ): void {
        // A simple handler for instantiation, as its execution isn't the focus here
        $handler = fn(): null => null;

        $actual = new ApiAction(
            $name,
            $path,
            $methods,
            $description,
            $handler,
        );

        self::assertSame(
            $name,
            $actual->getName(),
        );

        self::assertSame(
            $path,
            $actual->getPath(),
        );

        self::assertSame(
            $methods,
            $actual->getMethods(),
        );

        self::assertSame(
            $description,
            $actual->getDescription(),
        );
    }

    /**
     * @return array<string, array{name: string, path: string, methods: list<string>, description: string}>
     */
    public static function edgeCasesDataProvider(): array
    {
        return [
            'empty_name_and_description' => [
                'name' => '', // CASE TESTED
                'path' => '/empty-name',
                'methods' => ['GET'],
                'description' => '', // CASE TESTED
            ],
            'empty_path' => [
                'name' => 'Root Path',
                'path' => '', // CASE TESTED
                'methods' => ['GET'],
                'description' => 'Route for the root path.',
            ],
            'multiple_methods' => [
                'name' => 'Multi Method',
                'path' => '/multi',
                'methods' => ['GET', 'POST', 'PUT'], // CASE TESTED
                'description' => 'Route supporting multiple HTTP methods.',
            ],
            'single_method' => [
                'name' => 'Single Method',
                'path' => '/single',
                'methods' => ['PATCH'], // CASE TESTED
                'description' => 'Route with a single HTTP method.',
            ],
        ];
    }

    /**
     * @param array<string, int|string|bool> $params
     * 
     * @dataProvider invokableHandlerDataProvider
     */
    public function testProvidesInvokableHandler(
        Closure $handler,
        mixed $expected,
        array $params,
    ): void {
        $apiAction = new ApiAction(
            $this->name,
            $this->path,
            [$this->method],
            $this->description,
            $handler,
        );

        $routeHandler = $apiAction->getHandler();

        self::assertInstanceOf(RouteHandler::class, $routeHandler);

        $actual = $routeHandler($params);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{handler: Closure, expectedHandlerResult: mixed, handlerParams: array<string, int|string|bool>}>
     */
    public static function invokableHandlerDataProvider(): array
    {
        return [
            'handler_returns_string_with_params' => [
                'handler' => fn(array $params) => 'Result: ' . $params['id'],
                'expectedHandlerResult' => 'Result: 42',
                'handlerParams' => ['id' => '42'],
            ],
            'handler_returns_integer_no_params' => [
                'handler' => fn() => 123,
                'expectedHandlerResult' => 123,
                'handlerParams' => [],
            ],
            'handler_returns_array_with_params' => [
                'handler' => fn(array $params) => ['status' => 'ok', 'data' => $params],
                'expectedHandlerResult' => ['status' => 'ok', 'data' => ['item' => 'new']],
                'handlerParams' => ['item' => 'new'],
            ],
            'handler_returns_boolean' => [
                'handler' => fn() => true,
                'expectedHandlerResult' => true,
                'handlerParams' => [],
            ],
            'handler_with_no_params_expected' => [
                'handler' => fn() => 'OK',
                'expectedHandlerResult' => 'OK',
                'handlerParams' => [],
            ],
        ];
    }
}
