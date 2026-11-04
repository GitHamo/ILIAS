<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Routes\Auth;

use ILIAS\ApiGateway\Application\Routes\Auth\RefreshTokenRoute;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenSet;
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

#[CoversClass(RefreshTokenRoute::class)]
class RefreshTokenRouteTest extends TestCase
{
    private RefreshTokenRoute $route;
    private Authentication&MockObject $authentication;
    private Refinery\Factory&MockObject $refineryFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->route = new RefreshTokenRoute(
            $this->refineryFactory = $this->createMock(Refinery\Factory::class),
            $this->authentication = $this->createMock(Authentication::class),
        );
    }

    public function testCreatesActionWithCorrectRouteInfo(): void
    {
        $actual = $this->route;

        $this->assertSame('Refresh API Token', $actual->getName());
        $this->assertSame('Exchanges a valid refresh token for a new token set. This should be used when the access token has expired.', $actual->getDescription());
        $this->assertSame('/auth/refresh', $actual->getPath());
        $this->assertSame(HttpMethod::POST, $actual->getMethod());
    }

    public function testUsesComponentsToIssueToken(): void
    {
        $refreshToken = 'foo';
        $expected = ['foo' => 'bar'];

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

        $stringTransformation->expects($this->once())
            ->method('transform')
            ->willReturn($refreshToken);

        $stringGroup->expects($this->once())
            ->method('hasMinLength')
            ->with(1)
            ->willReturn($constraint);
        $constraint->expects($this->once())
            ->method('check')
            ->with($refreshToken);

        $this->authentication
            ->expects($this->once())
            ->method('refreshToken')
            ->with(trim($refreshToken))
            ->willReturn($tokenSet);

        $actual = $this->route->getAction()(['refresh_token' => $refreshToken], null);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('invalidParametersDataProvider')]
    public function testThrowsExceptionInCaseOfInvalidParameters(array $params): void
    {
        $stringGroup = $this->createMock(Refinery\String\Group::class);
        $toGroup = $this->createMock(Refinery\To\Group::class);
        $stringTransformation = $this->createMock(Refinery\Transformation::class);
        $constraint = $this->createMock(Constraint::class);

        $this->refineryFactory->method('string')->willReturn($stringGroup);
        $this->refineryFactory->method('to')->willReturn($toGroup);
        $toGroup->method('string')->willReturn($stringTransformation);
        $stringGroup->method('hasMinLength')->willReturn($constraint);

        // Simulate constraint failure
        $originalException = new UnexpectedValueException();
        $constraint->method('check')->willThrowException($originalException);

        try {
            $this->route->getAction()($params, null);
        } catch (InvalidArgumentException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertSame('Refresh token is missing, empty, or has an invalid type.', $e->getMessage());
            $this->assertSame($originalException, $e->getPrevious());
            return;
        }

        $this->fail('InvalidArgumentException was not thrown.');
    }
    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function invalidParametersDataProvider(): array
    {
        return [
            'empty refresh_token' => [['refresh_token' => '']],
        ];
    }
}
