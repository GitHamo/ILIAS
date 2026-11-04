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

namespace ILIAS\ApiGateway\Application\Bootstrap;

use Closure;
use ILIAS\ApiGateway\Application\Factories\MiddlewareFactory;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Routing\Action;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App as SlimApp;
use Slim\Routing\RouteCollectorProxy;

/**
 * Bridges domain Routes to Slim routes.
 */
readonly class RouteBootstrapper
{
    public function __construct(
        private RoutesRegistry $registry,
        private MiddlewareFactory $middlewareFactory,
        private ResponseHandler $responseHandler,
        private ServiceIdentity $identity,
    ) {
    }

    /**
     * @param SlimApp<\Psr\Container\ContainerInterface> $application
     */
    public function register(SlimApp $application): void
    {
        $application->group(
            $this->getBasePath(),
            function (RouteCollectorProxy $group): void {
                foreach ($this->registry->all() as $route) {
                    $slimRoute = $group
                        ->map(
                            [$route->getMethod()->value],
                            $route->getPath(),
                            $this->createRouteHandler($route->getAction()),
                        );

                    foreach ($route->getMiddlewares() as $middlewareClassname) {
                        $middleware = $this->middlewareFactory->create($middlewareClassname);
                        $slimRoute->add($middleware);
                    }
                }
            }
        );
    }

    private function createRouteHandler(Action $action): Closure
    {
        return fn(
            Request $request,
            Response $response,
            array $args,
        ): Response => ($this->responseHandler)(
            $request,
            $response,
            $args,
            $action,
        );
    }

    private function getBasePath(): string
    {
        $basePath = $this->identity->basePath;

        $basePath = trim($basePath);
        $basePath = trim($basePath, '/');
        $basePath = trim($basePath); // extra trimming for good measure

        return "/$basePath";
    }
}
