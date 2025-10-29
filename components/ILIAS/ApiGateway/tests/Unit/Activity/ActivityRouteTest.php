<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;

final class ActivityRouteTest extends TestCase
{
    private MockObject&Activity $activityMock;
    private MockObject&ActivityRouteHandler $handlerMock;
    private ActivityRoute $route;

    #[Override]
    protected function setUp(): void
    {
        $this->activityMock = $this->createMock(Activity::class);
        $this->handlerMock = $this->createMock(ActivityRouteHandler::class);

        $this->route = new ActivityRoute(
            $this->activityMock,
            $this->handlerMock
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
        $this->activityMock->method('getType')->willReturn($type);

        self::assertSame($expected, $this->route->getMethods());
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

    public function testHasAccessorToRouteHandler(): void
    {
        self::assertSame(
            $this->handlerMock,
            $this->route->getHandler(),
        );
    }

    public function testCreatesRouteFromActivity(): void
    {
        $actual = ActivityRoute::fromActivity($this->activityMock);

        self::assertEquals(
            new ActivityRoute(
                $this->activityMock,
                new ActivityRouteHandler($this->activityMock),
            ),
            $actual
        );

        self::assertInstanceOf(
            ActivityRouteHandler::class,
            $actual->getHandler(),
        );
    }
}
