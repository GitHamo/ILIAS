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

use ILIAS\ApiGateway\Application\Bootstrap\ErrorHandlerSetup;
use ILIAS\ApiGateway\Application\Bootstrap\MiddlewarePipeline;
use ILIAS\ApiGateway\Application\Bootstrap\RouteBootstrapper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App as SlimApp;

/**
 * Orchestrates the API Gateway by wiring core components together.
 * It manages the Slim application lifecycle through delegated bootstrap components.
 */
readonly class WebApp
{
    /**
     * @param SlimApp<\Psr\Container\ContainerInterface> $application
     */
    public function __construct(
        private SlimApp $application,
        private MiddlewarePipeline $middlewarePipeline,
        private RouteBootstrapper $routeBootstrapper,
        private ErrorHandlerSetup $errorHandlerSetup,
    ) {
    }

    public function run(?Request $request = null): void
    {
        $this->middlewarePipeline->apply(
            $this->application,
            $this->errorHandlerSetup,
        );

        $this->routeBootstrapper->register($this->application);

        $this->application->run($request);
    }
}
