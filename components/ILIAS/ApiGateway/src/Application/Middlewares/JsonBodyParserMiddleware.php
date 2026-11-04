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

use ILIAS\ApiGateway\Application\Exceptions\InvalidJsonException;
use ILIAS\ApiGateway\Application\Exceptions\MissingJsonPayloadException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Decodes JSON request bodies into PHP arrays.
 * Populates the Request's parsed body so handlers can access data protocol-agnostically.
 */
final readonly class JsonBodyParserMiddleware implements MiddlewareInterface
{
    private const array METHODS_WITH_BODY = ['POST', 'PUT', 'PATCH'];
    private const string CONTENT_TYPE_JSON = 'application/json';

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!$this->shouldParseBody($request)) {
            return $handler->handle($request);
        }

        $body = $request->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $contents = $body->getContents();

        if (trim($contents) === '') {
            throw new MissingJsonPayloadException($request);
        }

        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || !\is_array($data)) {
            throw new InvalidJsonException($request);
        }

        $request = $request->withParsedBody($data);

        return $handler->handle($request);
    }

    private function shouldParseBody(Request $request): bool
    {
        $method = $request->getMethod();

        if (!\in_array($method, self::METHODS_WITH_BODY, true)) {
            return false;
        }

        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_contains($contentType, self::CONTENT_TYPE_JSON);
    }
}
