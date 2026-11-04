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

namespace ILIAS\ApiGateway\Application\Handlers;

use ILIAS\ApiGateway\Application\RequestAttributes;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This class is responsible for the entire lifecycle of a successfully matched route.
 */
readonly class ResponseHandler
{
    public function __construct(
        private Webservice $service,
    ) {
    }

    /**
     * @param array<mixed, mixed> $args
     */
    public function __invoke(
        Request $request,
        Response $response,
        array $args,
        Action $action,
    ): Response {
        /** @var \ILIAS\ApiGateway\Auth\Domain\Model\AuthUser|null */
        $authUser = $request->getAttribute(RequestAttributes::AUTH_USER);
        $request = $request->withoutAttribute(RequestAttributes::AUTH_USER);
        /** @var array<string, mixed> $params */
        $params = $request->getAttribute(RequestAttributes::PARSED_PARAMETERS, []);

        $responseBody = $action(
            [...$params, ...$args],
            $authUser,
        );

        $responsePayload = $this->service->transform($responseBody);

        foreach ($responsePayload->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $response->getBody()->write($responsePayload->getBody());
        $response->getBody()->rewind();

        return $response;
    }
}
