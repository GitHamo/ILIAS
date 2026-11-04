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

use ILIAS\ApiGateway\Application\RequestAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Normalizes request parameters (query + parsed body) into a single array attribute.
 * For multipart/form-data requests, uploaded files are included as well.
 *
 * Precedence Rules: Body parameters override query parameters, and uploaded files
 * override body parameters if keys collide.
 */
final readonly class RequestParameterMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $params = array_replace(
            $request->getQueryParams(),
            (array) $request->getParsedBody(),
        );

        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        if (str_starts_with($contentType, 'multipart/form-data')) {
            $params = array_replace($params, $request->getUploadedFiles());
        }

        $request = $request->withAttribute(RequestAttributes::PARSED_PARAMETERS, $params);

        return $handler->handle($request);
    }
}
