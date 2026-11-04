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

namespace ILIAS\ApiGateway\Routing;

use ArrayAccess;
use ILIAS\ApiGateway\Activity\ActivityNamespaceFactory;
use ILIAS\ApiGateway\Activity\ActivityRouteFactory;
use ILIAS\ApiGateway\Application\Bootstrap\ServiceProvider as BaseServiceProvider;
use ILIAS\ApiGateway\Application\Factories\RoutesRegistryFactory;
use ILIAS\Component;

final readonly class ServiceProvider implements BaseServiceProvider
{
    /**
     * @param array|ArrayAccess<string, mixed> $define
     * @param array|ArrayAccess<string, mixed> $implement
     * @param array|ArrayAccess<string, mixed> $use
     * @param array|ArrayAccess<string, mixed> $contribute
     * @param array|ArrayAccess<string, mixed> $seek
     * @param array|ArrayAccess<string, mixed> $provide
     * @param array|ArrayAccess<string, mixed> $pull
     * @param array|ArrayAccess<string, mixed> $internal
     */
    public function register(
        array | ArrayAccess &$define,
        array | ArrayAccess &$implement,
        array | ArrayAccess &$use,
        array | ArrayAccess &$contribute,
        array | ArrayAccess &$seek,
        array | ArrayAccess &$provide,
        array | ArrayAccess &$pull,
        array | ArrayAccess &$internal,
    ): void {
        $define[] = Route::class;

        $internal[RoutesRegistryFactory::class] = static fn(): RoutesRegistryFactory => new RoutesRegistryFactory(
            new RouteRepository(
                $seek[Route::class],
            ),
            $use[Component\Activities\Repository::class],
            new ActivityRouteFactory(
                new ActivityNamespaceFactory(),
                $use[\ILIAS\UI\Factory::class]->input(),
            ),
        );
    }
}
