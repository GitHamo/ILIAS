<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;

final class ActivityRouteHandlerTest extends TestCase
{
    private ActivityRouteHandler $handler;
    private MockObject&Activity $activityMock;

    #[Override]
    protected function setUp(): void
    {
        $this->handler = new ActivityRouteHandler(
            $this->activityMock = $this->createMock(Activity::class),
        );
    }

    public function testValidatesCurrentUserIdBeforePerformActivity(): void
    {
        $userId = 0;
        $params = [
            'foo' => $userId,
            'bar' => 'baz',
        ];

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->with(
                self::identicalTo($userId),
                self::identicalTo($params),
            )
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->with(
                self::identicalTo($params),
            );

        ($this->handler)($params);
    }

    public function testDoesNotPerformActivityIfUserIsNotAuthorized(): void
    {
        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->willReturn(false);

        $this->activityMock->expects(self::never())
            ->method('perform');

        ($this->handler)([]);
    }
}
