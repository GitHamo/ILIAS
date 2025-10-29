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

use ILIAS\ApiGateway\Configuration\WebConfig;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\ResponseEmitter;

/**
 * The core idea is to have this class acts as an orchestrator that wires components together.
 * The actual handling of requests is delegated to specialized classes.
 * The Webservice is treated as a pure "formatting" or "view" layer, completely decoupled from the application's request/response lifecycle.
 */
final readonly class WebApp
{
    /**
     * @param App<\Psr\Container\ContainerInterface> $app
     */
    public function __construct(
        private WebConfig $configuration,
        private RoutesRegistry $registry,
        private RouteDispatcher $dispatcher,
        private ErrorHandler $errorHandler,
        private LoggerInterface $logger,
        private App $app,
        private ResponseFactory $responseFactory,
    ) {}

    public function run(): void
    {
        if (!$this->configuration->isEnabled) {
            $respone = $this->responseFactory->createResponse(503, 'Service Unavailable')
                ->withHeader('Content-Type', 'text/plain');

            $respone->getBody()->write('API Service is currently disabled.');

            (new ResponseEmitter())->emit($respone);

            return;
        }

        $this->registerMiddlewares();
        $this->registerRoutes();

        $this->app->run();
    }

    private function registerMiddlewares(): void
    {
        /**
         * The routing middleware should be added earlier than the error middleware.
         * Otherwise exceptions thrown from it will not be handled by the middleware.
         */
        $this->app->addRoutingMiddleware();

        // register default middlewares
        // register service middlewares

        /**
         * The error middleware should be added last. It will not handle any exceptions/errors for middleware added after it.
         */
        $this->registerErrorHandler();
    }

    private function registerErrorHandler(): void
    {
        $errorMiddleware = $this->app->addErrorMiddleware(
            $this->configuration->debugMode,
            $this->configuration->logErrors,
            $this->configuration->logErrorDetails,
            $this->logger,
        );

        $errorMiddleware->setDefaultErrorHandler($this->errorHandler);
    }

    private function registerRoutes(): void
    {
        // group instead of using $this->app->setBasePath()
        $this->app->group(
            $this->getBasePath(),
            function (\Slim\Routing\RouteCollectorProxy $group): void {
                foreach ($this->registry->all() as $route) {
                    $group
                        ->map(
                            $route->getMethods(),
                            $route->getPath(),
                            fn(Request $request, Response $response, array $args): Response => ($this->dispatcher)(
                                $request,
                                $response,
                                $args,
                                $route->getHandler(),
                            ),
                        );
                }
            }
        );

        //->add(new RestAuthenticationMiddleware(...));
    }

    private function getBasePath(): string
    {
        return '/' . trim($this->configuration->basePath, '/');
    }
}
