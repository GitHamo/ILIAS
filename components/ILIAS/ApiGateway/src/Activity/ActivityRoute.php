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

use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RouteHandler;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\ActivityType;
use Override;

class ActivityRoute implements Route
{
    public function __construct(
        private Activity $activity,
        private ActivityRouteHandler $handler,
        private ActivityNamespace $namespace,
    ) {}

    #[Override]
    public function getPath(): string
    {
        return $this->namespace->getPath();
    }

    #[Override]
    public function getMethods(): array
    {
        return match ($this->activity->getType()) {
            ActivityType::Command => ['POST'],
            ActivityType::Query => ['GET'],
        };
    }

    #[Override]
    public function getHandler(): RouteHandler
    {
        return $this->handler;
    }
}
