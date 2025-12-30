<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Application\Http\Action;

use ILIAS\ApiGateway\Auth\Application\Http\Action\RefreshTokenAction;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenSet;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefreshTokenAction::class)]
class RefreshTokenActionTest extends TestCase
{
    private RefreshTokenAction $action;
    private Authentication&MockObject $authentication;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new RefreshTokenAction(
            $this->authentication = $this->createMock(Authentication::class),
        );
    }

    public function testCreatesActionWithCorrectRouteInfo(): void
    {
        $actual = $this->action;

        $this->assertSame('Refresh API Token', $actual->getName());
        $this->assertSame('/auth/refresh', $actual->getPath());
        $this->assertSame(['POST'], $actual->getMethods());
        $this->assertSame('Exchanges a valid refresh token for a new token set. This should be used when the access token has expired.', $actual->getDescription());
    }

    public function testUsesComponentsToIssueToken(): void
    {
        $refreshToken = 'foo';
        $expected = ['foo' => 'bar'];

        $tokenSet = $this->createConfiguredMock(TokenSet::class, [
            'toArray' => $expected,
        ]);

        $this->authentication
            ->expects($this->once())
            ->method('refreshToken')
            ->with($this->identicalTo($refreshToken))
            ->willReturn($tokenSet);

        $actual = $this->action->getHandler()(['refresh_token' => $refreshToken], null);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('invalidParametersDataProvider')]
    public function testThrowsExceptionInCaseOfInvalidParameters(array $params): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token is missing or empty.');

        $this->action->getHandler()($params, null);
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function invalidParametersDataProvider(): array
    {
        return [
            'missing refresh_token' => [['foo' => 'bar']],
            'empty refresh_token' => [['refresh_token' => '']],
            'spaces refresh_token' => [['refresh_token' => '   ']],
        ];
    }
}
