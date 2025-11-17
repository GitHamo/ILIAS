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
use ILIAS\Data\Result;
use RuntimeException;

class ActivityRouteHandler implements RouteHandler
{
    private int $userId = 0; // @todo: temp user id

    public function __construct(
        private Activity $activity,
    ) {}

    #[\Override]
    public function __invoke(array $params): mixed
    {
        $parameters = $this->validate($params);

        if (false === $this->activity->isAllowedToPerform($this->userId, $parameters)) {
            // @todo: create own exception
            throw new RuntimeException('You are not allowed to perform this activity.', 403);
        }

        $result = $this->activity->perform($parameters);
        
        if($result instanceof Result) {

            if($result->isError()) {
                throw $result->error();
            }

            $factory = new \ILIAS\Data\Description\Factory();
            $output = $this->activity->getOutputDescription($factory);

            if(!$output->matches($result)) {
                throw new RuntimeException('Output description does not match result.');
            }

            return $result->value();
            return $this->activity->getOutputDescription($factory)->getPrimitiveRepresentation($result);
        }

        return $result ?? null;
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
