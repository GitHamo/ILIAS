<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
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

    public function testHasAccessorToRouteHandler(): void
    {
        self::assertSame(
            $this->handlerMock,
            $this->route->getHandler(),
        );
    }
}