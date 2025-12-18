<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Auth\Infrastructure;

use DateTimeImmutable;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ILIAS\ApiGateway\Application\Exception\AuthenticationException;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Auth\Domain\Model\AuthUser;
use ILIAS\ApiGateway\Auth\Domain\Model\Token;
use ILIAS\ApiGateway\Auth\Domain\Model\TokenPayload;
use ILIAS\ApiGateway\Auth\Domain\Service\TokenProvider;
use Override;
use UnexpectedValueException;

final readonly class JwtService implements TokenProvider
{
    public function __construct(
        private AuthConfig $authConfig,
    ) {}

    #[Override]
    public function generate(
        int $userId,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $expiresIn,
        bool $isRefresh = false,
    ): Token {
        /**
         * JWT Payload
         *
         * Essential (Practically Mandatory)
         * exp (Expiration Time): Identifies the expiration time on or after which the JWT MUST NOT be accepted for processing.
         * sub (Subject): Identifies the principal that is the subject of the JWT.
         *
         * Recommended
         * iss (Issuer): Identifies the principal that issued the JWT.
         * aud (Audience): Identifies the recipients that the JWT is intended for.
         *
         * Optional 
         * nbf (Not Before): Identifies the time before which the JWT MUST NOT be accepted for processing.
         * iat (Issued At): Identifies the time at which the JWT was issued.
         * jti (JWT ID): Provides a unique identifier for the JWT.
         * 
         */
        $payload = [
            'iss' => $this->authConfig->getIssuer(),
            'sub' => (string) $userId,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresIn->getTimestamp(),
        ];

        if ($isRefresh) {
            $payload['is_refresh'] = true;
        }

        $token = JWT::encode(
            $payload,
            $this->authConfig->getSecretKey(),
            $this->authConfig->getEncryptionAlgo(),
        );

        return new Token($token, $expiresIn);
    }

    #[Override]
    public function decode(string $token): TokenPayload
    {
        try {
            /**
             * @see https://github.com/firebase/php-jwt
             * 
             * JWT::decode() throws these exceptions
             * 
             * \InvalidArgumentException                    Provided key/key-array was empty or malformed
             * \DomainException                             Provided JWT is malformed
             * \UnexpectedValueException                    Provided JWT was invalid
             * \Firebase\JWT\SignatureInvalidException      Provided JWT was invalid because the signature verification failed
             * \Firebase\JWT\BeforeValidException           Provided JWT is trying to be used before it's eligible as defined by 'nbf'
             * \Firebase\JWT\BeforeValidException           Provided JWT is trying to be used before it's been created as defined by 'iat'
             * \Firebase\JWT\ExpiredException               Provided JWT has since expired, as defined by the 'exp' claim
             *
             */
            $decoded = JWT::decode(
                $token,
                new Key(
                    $this->authConfig->getSecretKey(),
                    $this->authConfig->getEncryptionAlgo(),
                ),
            );
        } catch (DomainException | UnexpectedValueException $e) {
            // catch 'firebase/php-jwt' exceptions in order not to leak third-party exceptions into the application.
            throw new AuthenticationException(
                'The provided token is invalid or expired.',
                $e,
            );
        }

        $payload = (array) $decoded;

        if (!isset($payload['iss']) || $payload['iss'] !== $this->authConfig->getIssuer()) {
            throw new AuthenticationException('Invalid token issuer.');
        }

        if (!isset($payload['sub']) || !is_numeric($payload['sub'])) {
            throw new AuthenticationException('Token subject (sub) claim is missing, empty, or not numeric.');
        }

        $userId = (int) $payload['sub'];
        $isRefresh = \array_key_exists('is_refresh', $payload) ? (bool) $payload['is_refresh'] : false;

        // The JWT::decode function throws an exception if the token is expired.
        // If we reach this point, the token is valid and not expired.

        return new TokenPayload(
            new AuthUser($userId),
            $isRefresh,
        );
    }
}
