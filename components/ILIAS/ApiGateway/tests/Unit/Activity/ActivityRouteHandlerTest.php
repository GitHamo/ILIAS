<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use Exception;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\Component\Activities\Activity;
use ILIAS\Data\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;
use RuntimeException;

final class ActivityRouteHandlerTest extends TestCase
{
    private ActivityRouteHandler $handler;
    private Activity&MockObject $activityMock;

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

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('You are not allowed to perform this activity.');
        self::expectExceptionCode(403);

        ($this->handler)([]);
    }

    public function testThrowsExceptionIfPerformResultHasError(): void
    {
        $result = $this->createConfiguredMock(Result::class, [
            'isError' => true,
            'error' => new \BadFunctionCallException(),
        ]);

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->willReturn($result);

        self::expectException(Exception::class);

        ($this->handler)([]);
    }
}
