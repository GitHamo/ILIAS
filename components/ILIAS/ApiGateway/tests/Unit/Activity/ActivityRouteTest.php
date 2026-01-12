<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityNamespace;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use ILIAS\Component\Activities\ObjectActivity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;
use Tests\Unit\Fixtures\FakeTestActivity;

final class ActivityRouteTest extends TestCase
{
    private Activity $activity;
    private MockObject&ActivityRouteHandler $handlerMock;
    private MockObject&ActivityNamespace $namespaceMock;
    private string $routePath = "/foo/bar/baz";
    /** @var array<string> */
    private array $middlewares = ['FOO::class', 'BAR::class'];
    private ActivityRoute $route;

    #[Override]
    protected function setUp(): void
    {
        $this->activity = new FakeTestActivity();
        $this->handlerMock = $this->createMock(ActivityRouteHandler::class);
        $this->namespaceMock = $this->createConfiguredMock(ActivityNamespace::class, [
            'getPath' => $this->routePath,
        ]);

        $this->route = new ActivityRoute(
            $this->activity,
            $this->handlerMock,
            $this->namespaceMock,
            $this->middlewares,
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('activityTypeProvider')]
    public function testGetMethodsReturnsCorrectHttpVerbsForActivityType(
        ActivityType $type,
        array $expected,
    ): void {
        $actual = new ActivityRoute(
            new FakeTestActivity($type),
            $this->handlerMock,
            $this->namespaceMock,
            $this->middlewares,
        );

        self::assertSame($expected, $actual->getMethods());
    }

    /**
     * @return array<string, list<mixed>>
     * @psalm-return array<string, array{ActivityType, list<string>}>
     */
    public static function activityTypeProvider(): array
    {
        return [
            'Command activity returns POST' => [ActivityType::Command, ['POST']],
            'Query activity returns GET' => [ActivityType::Query, ['GET']],
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
            $this->handlerMock,
            $this->namespaceMock,
            $this->middlewares,
        );

        self::assertSame(
            $this->routePath . '/{id}',
            $route->getPath(),
        );
    }

    public function testHasAccessorToRouteHandler(): void
    {
        self::assertSame(
            $this->handlerMock,
            $this->route->getHandler(),
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
