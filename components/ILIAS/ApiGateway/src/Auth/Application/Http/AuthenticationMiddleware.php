<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Auth\Application\Http;

use ILIAS\ApiGateway\Auth\Application\Exception\AuthenticationException;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Orchestrates the authentication process by extracting a token from the request
 * and passing it to a dedicated authentication service.
 */
final readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Authentication $authenticationService
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with(strtolower($authHeader), 'bearer ')) {
            throw new AuthenticationException('Authorization header missing or invalid.', 401);
        }

        $token = trim(substr($authHeader, 7)); // Length of "Bearer "

        if (empty($token)) {
            throw new AuthenticationException('Authorization token cannot be empty.', 401);
        }

        $authenticatedUser = $this->authenticationService->validateToken($token);

        // Add the fully populated user object to the request attributes for downstream use.
        $request = $request->withAttribute('authenticated_user', $authenticatedUser);

        return $handler->handle($request);
    }
}
