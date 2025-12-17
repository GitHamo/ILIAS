<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Application\Http\Action;

use ILIAS\ApiGateway\Auth\Application\Exception\AuthenticationException;
use ILIAS\ApiGateway\Auth\Application\Http\Action\IssueTokenAction;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenSet;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(IssueTokenAction::class)]
class IssueTokenActionTest extends TestCase
{
    private IssueTokenAction $action;
    private Authentication&MockObject $authentication;
    private UserRepository&MockObject $userRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new IssueTokenAction(
            $this->authentication = $this->createMock(Authentication::class),
            $this->userRepository = $this->createMock(UserRepository::class),
        );
    }

    public function testCreatesActionWithCorrectRouteInfo(): void
    {
        $actual = $this->action;

        $this->assertSame('Create API Token', $actual->getName());
        $this->assertSame('/auth/token', $actual->getPath());
        $this->assertSame(['POST'], $actual->getMethods());
        $this->assertSame('Authenticates a user and returns a new token set (access and refresh tokens).', $actual->getDescription());
    }

    public function testUsesComponentsToIssueToken(): void
    {
        $username = 'foo';
        $password = 'bar';
        $expected = ['foo' => 'bar'];

        $user = $this->createMock(AuthUser::class);
        $tokenSet = $this->createConfiguredMock(TokenSet::class, [
            'toArray' => $expected,
        ]);

        $this->userRepository
            ->expects($this->once())
            ->method('get')
            ->with($username, $password)
            ->willReturn($user);

        $this->authentication
            ->expects($this->once())
            ->method('createToken')
            ->with($this->identicalTo($user))
            ->willReturn($tokenSet);

        $actual = $this->action->getHandler()([
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('invalidParametersDataProvider')]
    public function testThrowsExceptionInCaseOfInvalidParameters(array $params): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Username or password is empty.');
        $this->expectExceptionCode(401);

        $this->action->getHandler()($params);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function invalidParametersDataProvider(): array
    {
        return [
            'missing username' => [['password' => 'bar']],
            'missing password' => [['username' => 'foo']],
            'empty username' => [['username' => '', 'password' => 'bar']],
            'empty password' => [['username' => 'foo', 'password' => '']],
            'spaces username' => [['username' => '   ', 'password' => 'bar']],
            'spaces password' => [['username' => 'foo', 'password' => '   ']],
        ];
    }
}
