<?php

declare(strict_types=1);

namespace Tests\Unit\Activity;

use BadFunctionCallException;
use DomainException;
use ILIAS\ApiGateway\Activity\ActivityAction;
use ILIAS\ApiGateway\Application\Exception\AuthorizationException;
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

final class ActivityActionTest extends TestCase
{
    private ActivityAction $action;
    private Activity&MockObject $activityMock;
    private AuthUser&MockObject $currentUserMock;

    #[Override]
    protected function setUp(): void
    {
        $this->action = new ActivityAction(
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
        $paramsWithUserId = array_merge($params, ['auth_user_id' => $userId]);

        $this->currentUserMock->expects(self::once())
            ->method('getId')
            ->willReturn($userId);

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->with(
                self::identicalTo($userId),
                self::identicalTo($paramsWithUserId),
            )
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->with(
                self::equalTo($paramsWithUserId),
            );

        ($this->action)($params, $this->currentUserMock);
    }

    public function testIgnoresTransformIdToObjectId(): void
    {
        $params = [
            'id' => 456,
            'foo' => 'bar',
        ];

        $expectedParams = [
            'id' => 456,
            'foo' => 'bar',
            'auth_user_id' => 0,
        ];

        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->with(0, self::equalTo($expectedParams))
            ->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->with(self::equalTo($expectedParams));

        ($this->action)($params, null);
    }

    public function testTransformsIdToObjectId(): void
    {
        $params = [
            'id' => 456,
            'foo' => 'bar',
        ];

        $expectedParams = [
            'object_id' => 456,
            'foo' => 'bar',
            'auth_user_id' => 0,
        ];

        $activityMock = $this->createMock(ObjectActivity::class);
        $action = new ActivityAction($activityMock);


        $activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->with(0, self::equalTo($expectedParams))
            ->willReturn(true);

        $activityMock->expects(self::once())
            ->method('perform')
            ->with(self::equalTo($expectedParams));

        ($action)($params, null);
    }

    public function testDoesNotPerformActivityIfUserIsNotAuthorized(): void
    {
        $this->activityMock->expects(self::once())
            ->method('isAllowedToPerform')
            ->willReturn(false);

        $this->activityMock->expects(self::never())
            ->method('perform');

        self::expectException(AuthorizationException::class);
        self::expectExceptionMessage('You are not allowed to perform this activity.');
        self::expectExceptionCode(403);

        ($this->action)([], null);
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

        ($this->action)([], $this->currentUserMock);
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

        ($this->action)([], $this->currentUserMock);
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

        $actual = ($this->action)([], null);

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

        $actual = ($this->action)([], null);

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

        $actual = ($this->action)([], null);

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

        ($this->action)([], null);
    }
}
