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

use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds transport-layer security headers, specifically Strict-Transport-Security (HSTS).
 *
 * This middleware *conditionally adds* the HSTS header to responses. When sent,
 * HSTS instructs clients (browsers) to communicate over HTTPS only for a specified duration
 * (currently 1 year: `max-age=31536000`). This is a critical protection against
 * protocol downgrade attacks.
 *
 * WARNING: HSTS is a "sticky" header. Misconfiguration (e.g., sending it over HTTP,
 * or if a domain/subdomain cannot fully serve HTTPS) can lead to an HSTS lockout,
 * rendering the site inaccessible for the `max-age` duration.
 * Ensure full HTTPS readiness before deploying.
 */
final readonly class TransportSecurityMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceIdentity $identity,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $isRequestSecure = $request->getUri()->getScheme() === 'https';
        $isBaseUrlConfiguredForHttps = str_starts_with(strtolower($this->identity->baseUrl), 'https://');

        if ($isRequestSecure && $isBaseUrlConfiguredForHttps) {
            return $response
                ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
