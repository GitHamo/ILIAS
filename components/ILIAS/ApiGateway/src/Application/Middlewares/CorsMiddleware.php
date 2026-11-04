<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ApiGateway\Application\Middlewares;

use ILIAS\HTTP\Response\ResponseFactory;
use ILIAS\HTTP\StatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Allows any domain to access the API (CORS).
 */
final readonly class CorsMiddleware implements MiddlewareInterface
{
    private const string ALLOW_ALL = '*';
    private const array ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private const array ALLOWED_HEADERS = ['Content-Type', 'Authorization', 'Accept'];
    private const int MAX_AGE = 86400; // 24 hours

    public function __construct(
        private ResponseFactory $responseFactory,
    ) {
    }

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');

        // If it's not a CORS request, just move on.
        if (trim($origin) === '') {
            return $handler->handle($request);
        }

        // Handle pre-flight (OPTIONS)
        if ($request->getMethod() === 'OPTIONS') {
            $requestedMethod = $request->getHeaderLine('Access-Control-Request-Method');
            if (!$this->isMethodAllowed($requestedMethod)) {
                return $handler->handle($request);
            }

            $response = $this->responseFactory->create()
                ->withStatus(StatusCode::HTTP_NO_CONTENT);

            return $this->addCorsHeaders($response);
        }

        // Handle normal requests
        $response = $handler->handle($request);

        return $this->addCorsHeaders($response);
    }

    private function isMethodAllowed(string $method): bool
    {
        return empty($method) || \in_array(strtoupper($method), self::ALLOWED_METHODS, true);
    }

    private function addCorsHeaders(Response $response): Response
    {
        // Note: Credentials (cookies) are blocked when using a wildcard origin.
        // To support them later, you must use a specific origin list and add
        // the 'Access-Control-Allow-Credentials: true' header.
        return $response
            ->withHeader('Access-Control-Allow-Origin', self::ALLOW_ALL)
            ->withHeader('Access-Control-Allow-Methods', implode(', ', self::ALLOWED_METHODS))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', self::ALLOWED_HEADERS))
            ->withHeader('Access-Control-Max-Age', (string) self::MAX_AGE)
            // ensures caches don't serve the wrong CORS headers to different origins.
            ->withHeader('Vary', 'Origin');
    }
}
