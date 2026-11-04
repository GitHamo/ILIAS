<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routes;

use Closure;
use ILIAS\ApiGateway\Application\Routes\ApiRoute;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Routing\HttpMethod;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiRouteTest extends TestCase
{
    private ApiRoute $apiRoute;
    private string $name = 'foo';
    private string $description = 'A test API action';
    private string $path = '/test';
    private HttpMethod $method = HttpMethod::PATCH;
    /** @var array<string> */
    private array $middlewares = ['Middleware1', 'Middleware2'];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiRoute = new ApiRoute(
            $this->name,
            $this->description,
            $this->path,
            $this->method,
            fn(): string => 'handled',
            $this->middlewares,
        );
    }

    public function testHasAccessorToActionName(): void
    {
        self::assertSame(
            $this->name,
            $this->apiRoute->getName(),
        );
    }

    public function testHasAccessorToDescription(): void
    {
        self::assertSame(
            $this->description,
            $this->apiRoute->getDescription(),
        );
    }

    public function testHasAccessorToPath(): void
    {
        self::assertSame(
            $this->path,
            $this->apiRoute->getPath(),
        );
    }

    public function testHasAccessorToMethod(): void
    {
        self::assertSame(
            $this->method,
            $this->apiRoute->getMethod(),
        );
    }

    public function testHasAccessorToInputSchema(): void
    {
        self::assertNull(
            $this->apiRoute->getInput(),
        );
    }

    public function testHasAccessorToOutputSchema(): void
    {
        self::assertNull(
            $this->apiRoute->getOutput(),
        );
    }

    public function testHasAccessorToMiddlewares(): void
    {
        self::assertSame(
            $this->middlewares,
            $this->apiRoute->getMiddlewares(),
        );
    }

    public function testCreatesInstanceWithoutOptionalParameters(): void
    {
        $actual = new ApiRoute(
            $this->name,
            $this->description,
            $this->path,
            $this->method,
            fn(): string => 'handled',
        );

        self::assertEmpty(
            $actual->getMiddlewares()
        );
    }

    #[DataProvider('edgeCasesDataProvider')]
    public function testHandlesEdgeCases(
        string $name,
        string $description,
        string $path,
        HttpMethod $method,
    ): void {
        // A simple action for instantiation, as its execution isn't the focus here
        $action = fn(): null => null;

        $actual = new ApiRoute(
            $name,
            $description,
            $path,
            $method,
            $action,
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
            $method,
            $actual->getMethod(),
        );

        self::assertSame(
            $description,
            $actual->getDescription(),
        );
    }

    /**
     * @return array<string, array{name: string, description: string, path: string, method: HttpMethod}>
     */
    public static function edgeCasesDataProvider(): array
    {
        return [
            'empty_name_and_description' => [
                'name' => '', // CASE TESTED
                'description' => '', // CASE TESTED
                'path' => '/empty-name',
                'method' => HttpMethod::GET,
            ],
            'empty_path' => [
                'name' => 'Root Path',
                'description' => 'Route for the root path.',
                'path' => '', // CASE TESTED
                'method' => HttpMethod::GET,
            ],
            // 'multiple_methods' => [
            //     'name' => 'Multi Method',
            //     'description' => 'Route supporting multiple HTTP methods.',
            //     'path' => '/multi',
            //     'methods' => ['GET', 'POST', 'PUT'], // CASE TESTED
            // ],
            'single_method' => [
                'name' => 'Single Method',
                'description' => 'Route with a single HTTP method.',
                'path' => '/single',
                'method' => HttpMethod::PATCH, // CASE TESTED
            ],
        ];
    }

    /**
     * @param array<string, int|string|bool> $params
     */
    #[DataProvider('invokableActionDataProvider')]
    public function testProvidesInvokableAction(
        Closure $action,
        mixed $expected,
        array $params,
        ?AuthUser $user,
    ): void {
        $apiRoute = new ApiRoute(
            $this->name,
            $this->description,
            $this->path,
            $this->method,
            $action,
        );

        $routeAction = $apiRoute->getAction();

        self::assertInstanceOf(Action::class, $routeAction);

        $actual = $routeAction($params, $user);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{action: Closure, expected: mixed, params: array<string, int|string|bool>, user: ?AuthUser}>
     */
    public static function invokableActionDataProvider(): array
    {
        return [
            'action_returns_string_with_params' => [
                'action' => fn(array $params, ?AuthUser $_user): string => 'Result: ' . $params['id'],
                'expected' => 'Result: 42',
                'params' => ['id' => '42'],
                'user' => null,
            ],
            'action_returns_integer_no_params' => [
                'action' => fn(array $_params, ?AuthUser $_user): int => 123,
                'expected' => 123,
                'params' => [],
                'user' => null,
            ],
            'action_returns_array_with_params' => [
                'action' => fn(array $params, ?AuthUser $_user): array => ['status' => 'ok', 'data' => $params],
                'expected' => ['status' => 'ok', 'data' => ['item' => 'new']],
                'params' => ['item' => 'new'],
                'user' => null,
            ],
            'action_returns_boolean' => [
                'action' => fn(array $_params, ?AuthUser $_user): true => true,
                'expected' => true,
                'params' => [],
                'user' => null,
            ],
            'action_with_no_params_expected' => [
                'action' => fn(array $_params, ?AuthUser $_user): string => 'OK',
                'expected' => 'OK',
                'params' => [],
                'user' => null,
            ],
            'action_uses_user_id' => [
                'action' => fn(array $params, ?AuthUser $user): string => $user instanceof AuthUser ? 'User ID: ' . $user->getId() : 'No user',
                'expected' => 'User ID: 99',
                'params' => [],
                'user' => new AuthUser(99),
            ],
            'action_no_user_provided' => [
                'action' => fn(array $params, ?AuthUser $user): string => $user instanceof AuthUser ? 'User ID: ' . $user->getId() : 'No user',
                'expected' => 'No user',
                'params' => [],
                'user' => null,
            ],
        ];
    }
}
