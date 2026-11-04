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

use ILIAS\ApiGateway\Application\Exceptions\NotAcceptableException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Makes sure the client can handle JSON responses.
 */
final readonly class JsonContentNegotiationMiddleware implements MiddlewareInterface
{
    private const string SUPPORTED_CONTENT_TYPES = 'application/json';

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        $acceptHeader = $request->getHeaderLine('Accept');

        if ($this->isJsonAccepted($acceptHeader)) {
            return $handler->handle($request);
        }

        throw new NotAcceptableException($request, self::SUPPORTED_CONTENT_TYPES);
    }

    private function isJsonAccepted(string $acceptHeader): bool
    {
        $normalizedHeader = strtolower(trim($acceptHeader));

        // If no Accept header is provided (or it is empty/whitespace),
        // RFC 9110 states that the client is assumed to accept all media types.
        if (
            $normalizedHeader === ''
            || str_contains($normalizedHeader, '*/*')
            || str_contains($normalizedHeader, 'application/*')
        ) {
            return true;
        }

        $mediaTypes = explode(',', $acceptHeader);

        foreach ($mediaTypes as $type) {
            // Strip parameters like ;q=1.0 or ;charset=UTF-8 and trim whitespace
            $cleanType = strtolower(trim(explode(';', $type)[0]));

            if ($cleanType === self::SUPPORTED_CONTENT_TYPES) {
                return true;
            }
        }

        return false;
    }
}
