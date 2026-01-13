<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use BadFunctionCallException;
use DomainException;
use Exception;
use ILIAS\ApiGateway\Activity\ActivityRouteHandler;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ObjectActivity;
use ILIAS\Data\Description\Description;
use ILIAS\Data\Description\Factory as DescriptionFactory;
use ILIAS\Data\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Override;
use RuntimeException;

final class ActivityRouteHandlerTest extends TestCase
{
    private ActivityRouteHandler $handler;
    private Activity&MockObject $activityMock;
    private AuthUser&MockObject $currentUserMock;

    #[Override]
    protected function setUp(): void
    {
        $this->handler = new ActivityRouteHandler(
            $this->activityMock = $this->createMock(Activity::class),
        );

        $this->currentUserMock = $this->createMock(AuthUser::class);
    }

    public function testValidatesCurrentUserIdBeforePerformActivity(): void
    {
        $userId = 123;
        $params = [
            'foo' => 'bar',
        ];

        $this->currentUserMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

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
                self::equalTo(['foo' => 'bar', 'auth_user_id' => $userId]),
            );

        ($this->handler)($params, $this->currentUserMock);
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

        ($this->handler)([], null);
    }

    public function testThrowsExceptionIfPerformResultHasError(): void
    {
        $result = $this->createConfiguredMock(Result::class, [
            'isError' => true,
            'error' => new BadFunctionCallException(),
        ]);

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->willReturn($result);

        self::expectException(BadFunctionCallException::class);

        ($this->handler)([], $this->currentUserMock);
    }

    public function testThrowsExceptionIfPerformResultHasStringError(): void
    {
        $result = $this->createConfiguredMock(Result::class, [
            'isError' => true,
            'error' => $errorMessage = 'error-string',
        ]);

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->willReturn($result);

        self::expectException(DomainException::class);
        self::expectExceptionMessage($errorMessage);

        ($this->handler)([], $this->currentUserMock);
    }

    public function testReturnsResultDirectlyIfNotAResultInstance(): void
    {
        $result = 'some-string-result';

        $this->activityMock->expects(self::once())
                           ->method('isAllowedToPerform')
                           ->willReturn(true);

        $this->activityMock->expects(self::once())
                           ->method('perform')
                           ->willReturn($result);

        $actual = ($this->handler)([], null);

        self::assertEquals($result, $actual);
    }

    public function testReturnsNullIfPerformReturnsNull(): void
    {
        $this->activityMock->expects(self::once())
                           ->method('isAllowedToPerform')
                           ->willReturn(true);

        $this->activityMock->expects(self::once())
                           ->method('perform')
                           ->willReturn(null);

        $actual = ($this->handler)([], null);

        self::assertNull($actual);
    }

    public function testReturnsResultValueOnSuccess(): void
    {
        $returnValue = 'some-value';
        $result = $this->createConfiguredMock(Result::class, [
            'isError' => false,
            'value' => $returnValue,
        ]);

        $descriptionMock = $this->createMock(Description::class);
        $descriptionMock->expects(self::once())
                        ->method('matches')
                        ->with($result)
                        ->willReturn(true);

        $this->activityMock->expects(self::once())
                           ->method('isAllowedToPerform')
                           ->willReturn(true);

        $this->activityMock->expects(self::once())
                           ->method('perform')
                           ->willReturn($result);

        $this->activityMock->expects(self::once())
                           ->method('getOutputDescription')
                           ->with(self::isInstanceOf(DescriptionFactory::class))
                           ->willReturn($descriptionMock);

        $actual = ($this->handler)([], null);

        self::assertEquals($returnValue, $actual);
    }

    public function testThrowsExceptionIfOutputDescriptionDoesNotMatch(): void
    {
        $result = $this->createConfiguredMock(Result::class, [
            'isError' => false,
        ]);

        $descriptionMock = $this->createMock(Description::class);
        $descriptionMock->expects(self::once())
                        ->method('matches')
                        ->with($result)
                        ->willReturn(false);

        $this->activityMock->expects(self::once())
                           ->method('isAllowedToPerform')
                           ->willReturn(true);

        $this->activityMock->expects(self::once())
                           ->method('perform')
                           ->willReturn($result);

        $this->activityMock->expects(self::once())
                           ->method('getOutputDescription')
                           ->willReturn($descriptionMock);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Output description does not match result.');

        ($this->handler)([], null);
    }
}
