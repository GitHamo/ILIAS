<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routes\Auth;

use ILIAS\ApiGateway\Application\Routes\Auth\IssueTokenRoute;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenSet;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Routing\HttpMethod;
use ILIAS\Refinery;
use ILIAS\Refinery\Constraint;
use ILIAS\Refinery\String\Group as StringGroup;
use ILIAS\Refinery\To\Group as ToGroup;
use ILIAS\Refinery\Transformation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(IssueTokenRoute::class)]
class IssueTokenRouteTest extends TestCase
{
    private IssueTokenRoute $route;
    private Authentication&MockObject $authentication;
    private UserRepository&MockObject $userRepository;
    private Refinery\Factory&MockObject $refineryFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->route = new IssueTokenRoute(
            $this->refineryFactory = $this->createMock(Refinery\Factory::class),
            $this->authentication = $this->createMock(Authentication::class),
            $this->userRepository = $this->createMock(UserRepository::class),
        );
    }

    public function testCreatesActionWithCorrectRouteInfo(): void
    {
        $actual = $this->route;

        $this->assertSame('Create API Token', $actual->getName());
        $this->assertSame('Authenticates a user and returns a new token set (access and refresh tokens).', $actual->getDescription());
        $this->assertSame('/auth/token', $actual->getPath());
        $this->assertSame(HttpMethod::POST, $actual->getMethod());
    }

    public function testUsesComponentsToIssueToken(): void
    {
        $username = 'foo';
        $password = ' bar ';
        $expected = ['foo' => 'bar'];

        $user = $this->createMock(AuthUser::class);
        $tokenSet = $this->createConfiguredMock(TokenSet::class, [
            'toArray' => $expected,
        ]);

        $stringGroup = $this->createMock(StringGroup::class);
        $toGroup = $this->createMock(ToGroup::class);
        $stringTransformation = $this->createMock(Transformation::class);
        $constraint = $this->createMock(Constraint::class);

        $this->refineryFactory->expects($this->once())
            ->method('string')
            ->willReturn($stringGroup);
        $this->refineryFactory->expects($this->once())
            ->method('to')
            ->willReturn($toGroup);
        $toGroup->expects($this->once())
            ->method('string')
            ->willReturn($stringTransformation);

        $stringTransformation->expects($this->exactly(2))
            ->method('transform')
            ->willReturnMap([
                [$username, $username],
                [$password, $password],
            ]);

        $stringGroup->expects($this->once())
            ->method('hasMinLength')
            ->with(1)
            ->willReturn($constraint);

        $constraint->expects($this->exactly(2))
            ->method('check')
            ->willReturnMap([
                [$username, null],
                [$password, null],
            ]);

        $this->userRepository
            ->expects($this->once())
            ->method('login')
            ->with($username, trim($password))
            ->willReturn($user);

        $this->authentication
            ->expects($this->once())
            ->method('createToken')
            ->with($this->identicalTo($user))
            ->willReturn($tokenSet);

        $actual = $this->route->getAction()([
            'username' => $username,
            'password' => $password,
        ], null);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string, string|null> $params
     */
    #[DataProvider('invalidParametersDataProvider')]
    public function testThrowsExceptionInCaseOfInvalidParameters(array $params): void
    {
        $stringGroup = $this->createMock(StringGroup::class);
        $toGroup = $this->createMock(ToGroup::class);
        $stringTransformation = $this->createMock(Transformation::class);
        $constraint = $this->createMock(Constraint::class);

        $this->refineryFactory->method('string')->willReturn($stringGroup);
        $this->refineryFactory->method('to')->willReturn($toGroup);
        $toGroup->method('string')->willReturn($stringTransformation);
        $stringGroup->method('hasMinLength')->willReturn($constraint);

        // Simulate constraint failure
        $originalException = new UnexpectedValueException('Username or password is empty or invalid.');
        $constraint->method('check')->willThrowException($originalException);

        try {
            $this->route->getAction()($params, null);
        } catch (InvalidArgumentException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertSame('Username or password is missing, empty, or has an invalid type.', $e->getMessage());
            $this->assertSame($originalException, $e->getPrevious());
            return;
        }

        $this->fail('InvalidArgumentException was not thrown.');
    }
    /**
     * @return array<string, array{0: array<string, string|null>}>
     */
    public static function invalidParametersDataProvider(): array
    {
        return [
            'username is null' => [['username' => null, 'password' => 'bar']],
        ];
    }
}
