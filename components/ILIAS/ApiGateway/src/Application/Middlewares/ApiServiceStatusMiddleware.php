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

use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\HTTP\Response\ResponseFactory;
use ILIAS\HTTP\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Checks if the API service is globally enabled. If not, it halts
 * execution and returns a 503 Service Unavailable response.
 */
final readonly class ApiServiceStatusMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceAccess $access,
        private ResponseFactory $responseFactory
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->access->isEnabled) {
            $response = $this->responseFactory->create();
            $response = $response->withStatus(StatusCode::HTTP_SERVICE_UNAVAILABLE)
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('API Service is currently disabled.');
            $response->getBody()->rewind();

            return $response;
        }

        return $handler->handle($request);
    }
}
