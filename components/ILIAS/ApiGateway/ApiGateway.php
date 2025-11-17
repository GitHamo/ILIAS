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

namespace ILIAS;

use ILIAS\ApiGateway\Activity\ActivityNamespaceFactory;
use ILIAS\ApiGateway\Activity\ActivityRouteFactory;
use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Application\HttpServiceFactory;
use ILIAS\ApiGateway\Application\WebAppFactory;
use ILIAS\ApiGateway\Logging\WebserviceLoggerFactory;
use ILIAS\ApiGateway\RestAppEntryPoint;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Routing\RouteStaticRepository;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\Component\Activities\Activity;
use ILIAS\Component\Activities\Repository as ActivityRepository;

class ApiGateway implements Component\Component
{
    #[\Override]
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\Endpoint =>
        new Component\Resource\Endpoint($this, "rest/index.php", "rest");
        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\OfComponent =>
        new Component\Resource\OfComponent($this, "rest/.htaccess", "rest");

        // @todo: should be fixed in original component
        $implement[HTTP\Response\ResponseFactory::class] = static fn(): HTTP\Response\ResponseFactory =>
        new HTTP\Response\ResponseFactoryImpl();

        // $define[] = ApiGateway\Contracts\Webservice::class;
        $define[] = ApiGateway\Routing\Route::class;

        // define internal services to build the main entry point
        /** @var WebserviceFactory */
        $internal['webservice_factory'] = static fn(): WebserviceFactory => new WebserviceFactory();
        /** @var HttpServiceFactory */
        $internal['http_service_factory'] = static fn(): HttpServiceFactory => new HttpServiceFactory();
        /** @var HTTP\Response\ResponseFactory */
        $internal['response_factory'] = static fn(): HTTP\Response\ResponseFactory => $use[HTTP\Response\ResponseFactory::class];
        /** @var RoutesRegistry */
        $internal['routes_registry'] = fn(): RoutesRegistry => RoutesRegistry::getInstance();
        /** @var RouteStaticRepository */
        $internal['routes_repository'] = static fn(): RouteStaticRepository => new RouteStaticRepository(
            $seek[ApiGateway\Routing\Route::class]
        );
        /** @var ActivityRepository */
        $internal['activities_repository'] = fn(): ActivityRepository => $use[Component\Activities\Repository::class];
        /** @var ActivityNamespaceFactory */
        $internal['activity_namespace_factory'] = static fn(): ActivityNamespaceFactory =>
        new ActivityNamespaceFactory();
        /** @var ActivityRouteFactory */
        $internal['activity_route_factory'] = static fn(): ActivityRouteFactory =>
        new ActivityRouteFactory($internal['activity_namespace_factory']);
        /** @var WebserviceLoggerFactory */
        $internal['webservice_logger_factory'] = static fn(): WebserviceLoggerFactory => new WebserviceLoggerFactory();
        /** @var WebAppFactory */
        $internal['webapp_factory'] = static fn(): WebAppFactory => new WebAppFactory(
            $internal['webservice_factory'],
            $internal['http_service_factory'],
            $internal['response_factory'],
            $internal['routes_registry'],
            new ActivityRoutesAutoloader(
                $internal['routes_registry'],
                $internal['activities_repository'],
                $internal['activity_route_factory'],
            ),
            new RoutesAutoloader(
                $internal['routes_registry'],
                $internal['routes_repository'],
            ),
            $internal['webservice_logger_factory'],
        );

        // use internal services to compose the final entry point
        $contribute[Component\EntryPoint::class] = static fn(): Component\EntryPoint\Base =>
        new RestAppEntryPoint($internal['webapp_factory']);

        // example ApiAction autoloaded
        $contribute[ApiGateway\Routing\Route::class] = static fn(): Route =>
        new ApiGateway\Routing\ApiAction(
            name: 'Ping',
            path: "/ping",
            methods: ['GET'],
            description: 'A simple ping pong route for testing purposes.',
            handler: fn(): string => 'Pong!',
        );

        // example to list all available activities and their corresponding routes
        $contribute[ApiGateway\Routing\Route::class] = static fn(): Route =>
        new ApiGateway\Examples\GetActivityListApiAction(
            $internal['activities_repository'],
            $internal['activity_route_factory'],
            '/rest',
        );

        // example activity autoloaded
        $contribute[Component\Activities\Activity::class] = static fn(): Activity =>
        new ApiGateway\Examples\ExampleActivity(
            $pull[Data\Factory::class],
        );
    }
}
