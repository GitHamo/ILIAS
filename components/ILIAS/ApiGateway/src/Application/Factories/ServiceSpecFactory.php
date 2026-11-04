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

namespace ILIAS\ApiGateway\Application\Factories;

use ILIAS\ApiGateway\Application\Middlewares\AuthenticationMiddleware;
use ILIAS\ApiGateway\Routing\HttpMethod;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\ServiceContract\Model\Operation;
use ILIAS\ApiGateway\ServiceContract\Model\ServiceSpec;

readonly class ServiceSpecFactory
{
    public function createFromRoutes(Route ...$routes): ServiceSpec
    {
        $operations = array_map([$this, 'createOperation'], $routes);

        return new ServiceSpec($operations);
    }

    private function createOperation(Route $route): Operation
    {
        $method = $route->getMethod();

        return new Operation(
            $method->value,
            $route->getPath(),
            $route->getName(),
            $route->getDescription(),
            \in_array(AuthenticationMiddleware::class, $route->getMiddlewares(), true),
            match ($method) {
                HttpMethod::GET, HttpMethod::HEAD => null,
                default => $route->getInput(),
            },
            $route->getOutput(),
        );
    }
}
