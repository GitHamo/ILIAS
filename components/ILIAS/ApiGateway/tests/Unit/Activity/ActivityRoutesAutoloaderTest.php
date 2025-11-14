<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ArrayIterator;
use ILIAS\ApiGateway\Activity\ActivityRoute;
use ILIAS\ApiGateway\Activity\ActivityRouteFactory;
use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\Repository as ActivityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ActivityRoutesAutoloaderTest extends TestCase
{
    private ActivityRoutesAutoloader $autoloader;
    private MockObject&RoutesRegistry $registry;
    private MockObject&ActivityRepository $repository;
    private MockObject&ActivityRouteFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->autoloader = new ActivityRoutesAutoloader(
            $this->registry = $this->createMock(RoutesRegistry::class),
            $this->repository = $this->createMock(ActivityRepository::class),
            $this->factory = $this->createMock(ActivityRouteFactory::class),
        );
    }

    public function testRegistersActivityRoute(): void
    {
        $activity = $this->createMock(Activity::class);
        $activities = new ArrayIterator([$activity, $activity]);
        $activityRoute = $this->createMock(ActivityRoute::class);

        $this->repository->expects(self::once())
            ->method('getActivitiesByName')
            ->with(self::identicalTo('/.*/'))
            ->willReturn($activities);

        $this->factory->expects(self::exactly(2))
            ->method('create')
            ->with(self::isInstanceOf(Activity::class))
            ->willReturn($activityRoute);

        $this->registry->expects(self::exactly(2))
            ->method('register')
            ->with(self::isInstanceOf(ActivityRoute::class));

        $this->autoloader->load();
    }

    public function testExcludesCoreActivities(): void
    {
        self::markTestSkipped('TODO');
    }

    public function testEmptyActivities(): void
    {
        $activities = new ArrayIterator([]);

        $this->repository->expects(self::once())
            ->method('getActivitiesByName')
            ->with(self::identicalTo('/.*/'))
            ->willReturn($activities);

        $this->factory->expects(self::never())->method('create');
        $this->registry->expects(self::never())->method('register');

        $this->autoloader->load();
    }
}
