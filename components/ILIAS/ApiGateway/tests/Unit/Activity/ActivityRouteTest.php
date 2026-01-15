<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityAction;
use ILIAS\ApiGateway\Activity\ActivityNamespace;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use ILIAS\Component\Activities\ObjectActivity;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Fixtures\FakeTestActivity;

final class ActivityRouteTest extends TestCase
{
    private Activity $activity;
    private MockObject&ActivityAction $actionMock;
    private MockObject&ActivityNamespace $namespaceMock;
    private string $routePath = "/foo/bar/baz";
    /** @var array<string> */
    private array $middlewares = ['FOO::class', 'BAR::class'];
    private ActivityRoute $route;

    #[Override]
    protected function setUp(): void
    {
        $this->activity = new FakeTestActivity();
        $this->actionMock = $this->createMock(ActivityAction::class);
        $this->namespaceMock = $this->createConfiguredMock(ActivityNamespace::class, [
            'getPath' => $this->routePath,
        ]);

        $this->route = new ActivityRoute(
            $this->activity,
            $this->actionMock,
            $this->namespaceMock,
            $this->middlewares,
        );
    }

    #[DataProvider('activityTypeProvider')]
    public function testGetMethodReturnsCorrectHttpVerbsForActivityType(
        ActivityType $type,
        string $expected,
    ): void {
        $actual = new ActivityRoute(
            new FakeTestActivity($type),
            $this->actionMock,
            $this->namespaceMock,
            $this->middlewares,
        );

        self::assertSame($expected, $actual->getMethod());
    }

    /**
     * @return array<string, list<mixed>>
     * @psalm-return array<string, array{ActivityType, string}>
     */
    public static function activityTypeProvider(): array
    {
        return [
            'Command activity returns POST' => [ActivityType::Command, 'POST'],
            'Query activity returns GET' => [ActivityType::Query, 'GET'],
        ];
    }

    public function testGetPathForNonObjectActivityReturnsBasePath(): void
    {
        self::assertSame(
            $this->routePath,
            $this->route->getPath(),
        );
    }

    public function testGetPathForObjectActivityAppendsId(): void
    {
        $objectActivity = $this->createMock(ObjectActivity::class);
        $route = new ActivityRoute(
            $objectActivity,
            $this->actionMock,
            $this->namespaceMock,
            $this->middlewares,
        );

        self::assertSame(
            $this->routePath . '/{id}',
            $route->getPath(),
        );
    }

    public function testHasAccessorToAction(): void
    {
        self::assertSame(
            $this->actionMock,
            $this->route->getAction(),
        );
    }

    public function testHasAccessorToMiddlewares(): void
    {
        self::assertSame(
            $this->middlewares,
            $this->route->getMiddlewares(),
        );
    }
}
