<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityNamespace;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;
use Tests\Unit\Fixtures\FakeTestActivity;

final class ActivityRouteTest extends TestCase
{
    private Activity $activity;
    private MockObject&ActivityRouteHandler $handlerMock;
    private ActivityNamespace $namespaceMock;
    private string $namespace = "Foo\\Bar\\Baz";
    private ActivityRoute $route;

    #[Override]
    protected function setUp(): void
    {
        $this->activity = new FakeTestActivity();
        $this->handlerMock = $this->createMock(ActivityRouteHandler::class);
        $this->namespaceMock = ActivityNamespace::create(
            $this->namespace,
        );

        $this->route = new ActivityRoute(
            $this->activity,
            $this->handlerMock,
            $this->namespaceMock,
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

    public function testHasAccessorToNamespacePath(): void
    {
        $expected = "/foo/bar/Baz";

        self::assertSame(
            $expected,
            $this->route->getPath(),
        );
    }

    public function testHasAccessorToRouteHandler(): void
    {
        self::assertSame(
            $this->handlerMock,
            $this->route->getHandler(),
        );
    }

    public function testCreatesRouteFromActivity(): void
    {
        $actual = ActivityRoute::fromActivity($this->activity);

        self::assertEquals(
            new ActivityRoute(
                $this->activity,
                new ActivityRouteHandler($this->activity),
                ActivityNamespace::create(get_class($this->activity)),
            ),
            $actual
        );

        self::assertInstanceOf(
            ActivityRouteHandler::class,
            $actual->getHandler(),
        );
    }
}
