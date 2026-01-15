<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityAction;
use ILIAS\ApiGateway\Activity\ActivityNamespace;
use ILIAS\ApiGateway\Activity\ActivityNamespaceFactory;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteFactory;
use ILIAS\ApiGateway\Middleware\AuthenticationMiddleware;
use ILIAS\Component\Activities\Activity;
use PHPUnit\Framework\TestCase;

class ActivityRouteFactoryTest extends TestCase
{
    public function testCreatesRouteFromActivity(): void
    {
        $activity = $this->createMock(Activity::class);
        $namespaceFactory = $this->createMock(ActivityNamespaceFactory::class);
        $namespace = $this->createMock(ActivityNamespace::class);

        $namespaceFactory->expects(self::once())
            ->method('create')
            ->with(\get_class($activity))
            ->willReturn($namespace);

        $expected = new ActivityRoute(
            $activity,
            new ActivityAction($activity),
            $namespace,
            [
                AuthenticationMiddleware::class,
            ],
        );

        $factory = new ActivityRouteFactory(
            $namespaceFactory,
        );

        $actual = $factory->create($activity);

        self::assertEquals($expected, $actual);

        self::assertInstanceOf(
            ActivityAction::class,
            $actual->getAction(),
        );
    }
}
