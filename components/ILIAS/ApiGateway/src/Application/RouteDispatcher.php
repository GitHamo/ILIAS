<?php

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
final readonly class RouteDispatcher
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
