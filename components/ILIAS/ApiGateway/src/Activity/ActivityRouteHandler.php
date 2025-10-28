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

use ILIAS\ApiGateway\Routing\RouteHandler;
use ILIAS\Component\Activities\Activity;

class ActivityRouteHandler implements RouteHandler
{
    private int $userId = 0; // @todo: temp user id

    public function __construct(
        private Activity $activity,
    ) {}

    #[\Override]
    public function __invoke(array $params): void
    {
        $parameters = $this->validate($params);

        if ($this->activity->isAllowedToPerform($this->userId, $parameters)) {
            $this->activity->perform($parameters);
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function validate(array $parameters): array
    {
        return $parameters;
    }
}
