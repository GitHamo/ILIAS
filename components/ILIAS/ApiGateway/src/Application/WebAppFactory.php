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
use ILIAS\ApiGateway\Contracts\WebConfig;
use ILIAS\ApiGateway\Logging\WebserviceLogger;
use ILIAS\ApiGateway\Logging\WebserviceLoggerFactory;
use ILIAS\ApiGateway\Routing\RoutesAutoloader;
use ILIAS\ApiGateway\Routing\RoutesRegistry;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\HTTP\Response\ResponseFactory;
use ilLoggerFactory;
use Psr\Log\LoggerInterface;

final readonly class WebAppFactory
{
    public function __construct(
        private WebserviceFactory $webserviceFactory,
        private HttpServiceFactory $httpServiceFactory,
        private ResponseFactory $responseFactory,
        private RoutesRegistry $registry,
        private ActivityRoutesAutoloader $activityRoutesAutoloader,
        private RoutesAutoloader $routesAutoloader,
        private WebserviceLoggerFactory $loggerFactory,
    ) {}

    public function create(WebConfig $config): WebApp
    {
        $this->registerRoutes();

        $webservice = $this->webserviceFactory->create($config);
        $executor = $this->httpServiceFactory->createRouteExecutor($webservice);
        $logger = $this->loggerFactory->create($config->getProtocol()->value);
        $errorHandler = $this->httpServiceFactory->createErrorHandler(
            $webservice,
            $config,
            $logger,
            $this->responseFactory,
        );
        /**
         * @var \Slim\App<\Psr\Container\ContainerInterface>
         */
        $application = $this->httpServiceFactory->createWebApplication();

        return new WebApp(
            $config,
            $this->registry,
            $executor,
            $errorHandler,
            $logger,
            $this->responseFactory,
            $application, // slim app
        );
    }

    private function registerRoutes(): void
    {
        $this->activityRoutesAutoloader->load();
        $this->routesAutoloader->load();
    }
}
