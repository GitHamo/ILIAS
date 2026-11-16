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

use ArrayAccess;
use ILIAS\ApiGateway\Application\Bootstrap\ServiceProvider as BaseServiceProvider;
use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ILIAS\ApiGateway\Application\Factories\HttpServiceFactory;
use ILIAS\ApiGateway\Application\Factories\RoutesRegistryFactory;
use ILIAS\ApiGateway\Application\Factories\WebAppFactory;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\DatabaseRateLimitRepository;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\RateLimitingService;
use ILIAS\ApiGateway\RestAppEntryPoint;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\Component;
use ILIAS\Database\Connection;
use ILIAS\HTTP;
use ILIAS\Logging\Logger\LoggerFactoryInterface;
use ILIAS\Refinery;
use Psr\Http\Server\MiddlewareInterface;

final readonly class ServiceProvider implements BaseServiceProvider
{
    public function __construct(
        private Component\Component $component
    ) {
    }

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
        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\OfComponent =>
        new Component\Resource\Endpoint($this->component, "rest/swagger.php");

        $internal[WebAppFactory::class] = static fn(): WebAppFactory =>
        new WebAppFactory(
            $internal[HttpConfigFactory::class],
            new HttpServiceFactory(),
            new WebserviceFactory(),
            $internal[RoutesRegistryFactory::class],
            new MiddlewareRepository(
                $seek[MiddlewareInterface::class],
            ),
            new HTTP\Response\ResponseFactoryImpl(),
            $use[LoggerFactoryInterface::class],
            new RateLimitingService(
                new DatabaseRateLimitRepository(
                    $pull[Connection::class],
                ),
            ),
        );

        $contribute[Component\EntryPoint::class] = static fn(): Component\EntryPoint =>
        new RestAppEntryPoint(
            $internal[WebAppFactory::class],
        );

        /**
         * API Application Endpoints
         */

        ## /ping
        $contribute[Route::class] = static fn(): Route => new Routes\PingRoute();

        ## /auth/token
        $contribute[Route::class] = static fn(): Route =>
        new Routes\Auth\IssueTokenRoute(
            $pull[Refinery\Factory::class],
            $internal[Authentication::class],
            $internal[UserRepository::class],
        );

        ## /auth/refresh
        $contribute[Route::class] = static fn(): Route =>
        new Routes\Auth\RefreshTokenRoute(
            $pull[Refinery\Factory::class],
            $internal[Authentication::class],
        );
    }
}
