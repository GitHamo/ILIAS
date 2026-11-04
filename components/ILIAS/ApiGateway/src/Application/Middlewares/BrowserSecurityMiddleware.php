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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds security headers specifically for browser-based clients to protect
 * against web vulnerabilities like XSS, clickjacking, and content-sniffing.
 */
final readonly class BrowserSecurityMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response
            // Content-Security-Policy: Restricts sources for content to prevent XSS. 'frame-ancestors' prevents clickjacking.
            ->withHeader('Content-Security-Policy', "default-src 'self'; frame-ancestors 'none';")

            // X-Content-Type-Options: Prevents browsers from MIME-sniffing a response away from the declared content-type.
            ->withHeader('X-Content-Type-Options', 'nosniff')

            // X-Frame-Options: Provides legacy browser support for preventing clickjacking.
            ->withHeader('X-Frame-Options', 'DENY')

            // X-XSS-Protection: Disables the browser's built-in XSS auditor, as modern CSP is more robust.
            ->withHeader('X-XSS-Protection', '0');
    }
}
