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
    private Result&MockObject $dataResultMock;
    private Description&MockObject $dataDescriptionMock;
    private AuthUser&MockObject $currentUserMock;

    #[Override]
    protected function setUp(): void
    {
        $this->action = new ActivityAction(
            $this->activityMock = $this->createConfiguredMock(Activity::class, [
                'maybePerformAs' => $this->dataResultMock = $this->createMock(Result::class),
                'getOutputDescription' => $this->dataDescriptionMock = $this->createMock(Description::class),
            ]),
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
            ->method('maybePerformAs')
            ->with(
                self::equalTo($userId),
                self::equalTo($paramsWithUserId),
            );

        $this->dataDescriptionMock->expects(self::once())
            ->method('matches')
            ->with(self::identicalTo($this->dataResultMock))
            ->willReturn(true);

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

        $this->dataDescriptionMock->method('matches')->willReturn(true);

        $this->activityMock->expects(self::once())
            ->method('maybePerformAs')
            ->with(self::equalTo(0), self::equalTo($expectedParams));


        ($this->action)($params, null);
    }

    public function testThrowsExceptionIfPerformResultHasError(): void
    {
        $this->activityMock->expects(self::once())
            ->method('maybePerformAs')
            ->willReturn($this->dataResultMock);

        $this->dataResultMock->expects(self::once())
            ->method('isError')
            ->willReturn(true);

        $this->dataResultMock->expects(self::once())
            ->method('error')
            ->willReturn(new BadFunctionCallException());

        self::expectException(BadFunctionCallException::class);

        ($this->action)([], $this->currentUserMock);
    }

    public function testThrowsExceptionIfPerformResultHasStringError(): void
    {
        $errorMessage = 'error-string';

        $this->dataResultMock->expects(self::once())
            ->method('isError')
            ->willReturn(true);

        $this->dataResultMock->expects(self::once())
            ->method('error')
            ->willReturn($errorMessage);

        self::expectException(DomainException::class);
        self::expectExceptionMessage($errorMessage);

        ($this->action)([], $this->currentUserMock);
    }

    public function testReturnsResultDirectlyIfNotAResultInstance(): void
    {
        $this->markTestSkipped('This is not possible anymore');

        $result = 'some-string-result';

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->willReturn($result);

        $actual = ($this->action)([], null);

        self::assertEquals($result, $actual);
    }

    public function testReturnsNullIfPerformReturnsNull(): void
    {
        $this->markTestSkipped('This is not possible anymore');

        $this->activityMock->expects(self::once())
            ->method('perform')
            ->willReturn(null);

        $actual = ($this->action)([], null);

        self::assertNull($actual);
    }

    public function testReturnsResultValueOnSuccess(): void
    {
        $returnValue = 'some-value';

        $this->dataDescriptionMock->method('matches')->willReturn(true);

        $this->dataResultMock->expects(self::once())
            ->method('isError')
            ->willReturn(false);

        $this->dataResultMock->expects(self::once())
            ->method('value')
            ->willReturn($returnValue);

        $this->activityMock->expects(self::once())
            ->method('getOutputDescription')
            ->with(self::isInstanceOf(DescriptionFactory::class));

        $actual = ($this->action)([], null);

        self::assertEquals($returnValue, $actual);
    }

    public function testThrowsExceptionIfOutputDescriptionDoesNotMatch(): void
    {
        $this->dataDescriptionMock->method('matches')->willReturn(false);

        $this->dataResultMock->expects(self::once())
            ->method('isError')
            ->willReturn(false);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage('Output description does not match result.');

        ($this->action)([], null);
    }
}
