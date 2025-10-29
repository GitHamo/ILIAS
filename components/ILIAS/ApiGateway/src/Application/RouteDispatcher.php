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

namespace ILIAS\ApiGateway\Application;

use ILIAS\ApiGateway\Models\Payload;
use ILIAS\ApiGateway\Routing\RouteHandler;
use ILIAS\ApiGateway\Webservice;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This class is responsible for the entire lifecycle of a successfully matched route.
 */
readonly class RouteDispatcher
{
    public function __construct(
        private Webservice $service,
    ) {}

    /**
     * @param array<mixed, mixed> $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args,
        RouteHandler $action,
    ): Response {
        $params = $request->getQueryParams();

        $responseBody = $action(
            array_merge($params, $args)
        );

        $successPayload = new Payload($responseBody);
        $responsePayload = $this->service->handle($successPayload)->getBody();

        $response->getBody()->write($responsePayload);

        return $response;
    }
}
