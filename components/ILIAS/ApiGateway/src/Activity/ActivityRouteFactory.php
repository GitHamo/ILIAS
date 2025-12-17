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

use ILIAS\ApiGateway\Auth\Application\Http\AuthenticationMiddleware;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\Component\Activities\Activity;

readonly class ActivityRouteFactory
{
    public function __construct(
        private ActivityNamespaceFactory $namespaceFactory,
        private Authentication $authenticationService,
    ) {}

    public function create(Activity $activity): ActivityRoute
    {
        return new ActivityRoute(
            $activity,
            new ActivityRouteHandler($activity),
            $this->namespaceFactory->create(\get_class($activity)),
            [
                new AuthenticationMiddleware($this->authenticationService),
            ],
        );
    }
}
