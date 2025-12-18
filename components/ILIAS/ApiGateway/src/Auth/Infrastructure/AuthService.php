<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Auth\Infrastructure;

use DomainException;
use ILIAS\ApiGateway\Application\Exception\AuthenticationException;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Auth\Domain\Model\RefreshToken;
use ILIAS\ApiGateway\Auth\Domain\Model\Token;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenSet;
use ILIAS\ApiGateway\Auth\Domain\Repository\RefreshTokenRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Auth\Domain\Service\TokenProvider;
use Override;

/**
 * Orchestrates the authentication process by coordinating the token provider and refresh token repository.
 */
final readonly class AuthService implements Authentication
{
    public function __construct(
        private TokenProvider $tokenProvider,
        private RefreshTokenRepository $refreshTokenRepository,
        private AuthConfig $authConfig,
    ) {}

    #[Override]
    public function createToken(AuthUser $user): TokenSet
    {
        $userId = $user->getId();
        $issuedAt = new \DateTimeImmutable();
        $accessTokenExpiry = $issuedAt->modify('+' . $this->authConfig->getAccessTokenExpiry() . ' seconds');
        $refreshTokenExpiry = $issuedAt->modify('+' . $this->authConfig->getRefreshTokenExpiry() . ' seconds');

        $accessToken = $this->tokenProvider->generate($userId, $issuedAt, $accessTokenExpiry);
        $refreshToken = $this->tokenProvider->generate($userId, $issuedAt, $refreshTokenExpiry, true);

        // Hash the refresh token and save it to the database
        $this->saveRefreshToken($user, $refreshToken);

        return new TokenSet($accessToken, $refreshToken);
    }

    #[Override]
    public function refreshToken(string $refreshToken): TokenSet
    {
        $payload = $this->tokenProvider->decode($refreshToken);

        if (false === $payload->isRefresh()) {
            throw new DomainException('Token is not a refresh token.');
        }

        // validate the refresh token against the database
        $tokenHash = $this->hashToken($refreshToken);
        $storedToken = $this->refreshTokenRepository->find($tokenHash);

        if ($storedToken === null || $storedToken->isRevoked() || $storedToken->isExpired()) {
            throw new AuthenticationException('Refresh token is invalid or has been revoked.');
        }

        // revoke the old token after use (rotation)
        $this->refreshTokenRepository->revoke($storedToken);

        $user = $payload->getUser();

        // issue a new set of tokens
        return $this->createToken($user);
    }

    #[Override]
    public function validateToken(string $token): AuthUser
    {
        $payload = $this->tokenProvider->decode($token);

        return $payload->getUser();
    }

    private function saveRefreshToken(AuthUser $user, Token $token): void
    {
        $hash = $this->hashToken($token->getToken());

        $refreshToken = new RefreshToken(
            $user->getId(),
            $hash,
            $token->getExpiresIn()
        );

        $this->refreshTokenRepository->save($refreshToken);
    }

    private function hashToken(string $token): string
    {
        return hash($this->authConfig->getHashAlgo(), $token);
    }
}
