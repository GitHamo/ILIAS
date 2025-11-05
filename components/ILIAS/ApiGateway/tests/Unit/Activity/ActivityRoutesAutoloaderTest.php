<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityNamespace;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Fixtures\FakeTestActivity;

final class ActivityRoutesAutoloaderTest extends TestCase
{
    private ActivityRoutesAutoloader $autoloader;
    private MockObject&RoutesRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->autoloader = new ActivityRoutesAutoloader(
            $this->registry = $this->createMock(RoutesRegistry::class),
        );
    }

    public function testRegistersActivityAsRoute(): void
    {
        $activity = new FakeTestActivity();

        $this->registry->expects(self::once())
            ->method('register')
            ->with(
                self::equalTo(
                    new ActivityRoute(
                        $activity,
                        new ActivityRouteHandler($activity),
                        ActivityNamespace::create(get_class($activity)),
                    )
                ),
            )
        ;

        $this->autoloader->load($activity);
    }
}
