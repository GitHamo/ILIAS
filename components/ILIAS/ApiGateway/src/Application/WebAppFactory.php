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

use ILIAS\ApiGateway\Activity\ActivityRoutesAutoloader;
use ILIAS\ApiGateway\Configuration\WebConfig;
use ILIAS\ApiGateway\Logging\WebserviceLogger;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\ServiceProtocol;
use ILIAS\ApiGateway\Webservices\RestWebservice;
use ilLoggerFactory;
use InvalidArgumentException;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * @codeCoverageIgnore
 * 
 * This class is meant to isolate legacy dependancies and work as dependancy-provider to WebApp & hold creation logic for each webservice.
 * It directly calls the ILIAS logging system (ilLoggerFactory), which pulls in a lot of dependencies that aren't available in the test environment.
 * Therefore unit testing it would be hard. An integration test is more suitable at this point.
 */
final class WebAppFactory
{
    /**
     * @param \ILIAS\ApiGateway\Routing\Route[] $routes
     */
    public static function create(
        ServiceProtocol $protocol,
        string $baseUrl,
        string $basePath,
        bool $isEnabled,
        bool $isDebugEnabled,
        bool $logErrors,
        bool $logErrorDetails,
        array $routes = [],
    ): WebApp {
        $registry = RoutesRegistry::getInstance();
        $activityAutoloader = new ActivityRoutesAutoloader($registry);

        array_walk($routes, [$registry, 'register']);

        /** @psalm-suppress NoValue */
        $activityAutoloader->load(...[
            // @todo: fetch & init activities automatically
            // new \ILIAS\ApiGateway\Examples\ExampleActivity(),
        ]);

        $configs = new WebConfig(
            $baseUrl,
            $basePath,
            $isEnabled,
            $isDebugEnabled,
            $logErrors,
            $logErrorDetails,
        );

        $webservice = match ($protocol) {
            ServiceProtocol::REST => new RestWebservice($configs),
            /**
             * As a defensive mechanism for truly unhandled cases, so testing would be hard as
             * this should never be hit. In production ALL ServiceProtocol enum cases are 
             * translated into exisiting webservices. Therefore, it is ignored from code coverage.
             */
            // @codeCoverageIgnoreStart
            default => throw new InvalidArgumentException(
                sprintf("Unsupported service protocol: %s", $protocol->name)
            ),
            // @codeCoverageIgnoreEnd
        };

        $dispatcher = new RouteDispatcher($webservice);

        $responseFactory = new ResponseFactory(); // slim response factory

        $logger = new WebserviceLogger(
            ilLoggerFactory::getLogger(
                $webservice->getProtocol()->value,
            )
        );

        /** @var \Slim\App<\Psr\Container\ContainerInterface> */
        $app = AppFactory::create();

        $errorHandler = new ErrorHandler(
            $webservice,
            $configs,
            $logger,
            $responseFactory,
        );

        return new WebApp(
            $configs,
            $registry,
            $dispatcher,
            $errorHandler,
            $logger,
            $app, // slim app
            $responseFactory,

        );
    }
}
