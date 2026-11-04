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

namespace ILIAS\ApiGateway\Activity;

use ILIAS\ApiGateway\Application\Middlewares\AuthenticationMiddleware;
use ILIAS\Component\Activities\Activity;
use ILIAS\UI\Component\Input\Factory;

readonly class ActivityRouteFactory
{
    public function __construct(
        private ActivityNamespaceFactory $namespaceFactory,
        private Factory  $inputFactory,
    ) {
    }

    public function create(Activity $activity): ActivityRoute
    {
        $action = new ActivityAction($activity, $this->inputFactory);
        $namespace = $this->namespaceFactory->create($activity::class);
        $middlewares = [
            AuthenticationMiddleware::class,
        ];

        return new ActivityRoute(
            $activity,
            $action,
            $namespace,
            $middlewares,
        );
    }
}
