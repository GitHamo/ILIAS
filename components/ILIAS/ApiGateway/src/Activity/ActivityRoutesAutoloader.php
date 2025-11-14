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

use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\Component\Activities\Repository as ActivityRepository;

final readonly class ActivityRoutesAutoloader
{
    public function __construct(
        private RoutesRegistry $routesRegistry,
        private ActivityRepository $activityRepository,
        private ActivityRouteFactory $activityRouteFactory,
    ) {}

    public function load(): void
    {
        $activites = $this->activityRepository->getActivitiesByName("/.*/");
        // @todo: exclude ILIAS\Setup activities
        $routes = array_map([$this->activityRouteFactory, 'create'], iterator_to_array($activites));

        array_walk($routes, [$this->routesRegistry, 'register']);
    }
}
