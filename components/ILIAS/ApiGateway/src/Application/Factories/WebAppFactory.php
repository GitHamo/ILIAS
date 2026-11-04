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

namespace ILIAS\ApiGateway\Application\Factories;

use ILIAS\ApiGateway\Application\Bootstrap;
use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Application\WebApp;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use ILIAS\ApiGateway\Webservice\WebserviceLoggerFactory;
use ILIAS\HTTP\Response\ResponseFactory;
use Psr\Log\LoggerInterface;

readonly class WebAppFactory
{
    private const string COMPONENT_NAME = "webservices";

    public function __construct(
        private HttpConfigFactory $httpConfigFactory,
        private HttpServiceFactory $httpServiceFactory,
        private WebserviceFactory $webserviceFactory,
        private RoutesRegistryFactory $routesRegistryFactory,
        private MiddlewareRepository $middlewareRepository,
        private ResponseFactory $responseFactory,
        private WebserviceLoggerFactory $loggerFactory,
        private RateLimiting $rateLimitingService,
    ) {
    }

    public function create(ServiceProtocol $protocol): WebApp
    {
        $webConfig = $this->httpConfigFactory->createWebConfig($protocol);
        $authConfig = $this->httpConfigFactory->createAuthConfig();
        $registry = $this->routesRegistryFactory->create();
        $webservice = $this->webserviceFactory->create($webConfig);
        $logger = $this->loggerFactory->create(self::COMPONENT_NAME);

        $middlewareFactory = $this->createMiddlewareFactory($webConfig, $authConfig, $logger);
        $responseHandler = $this->createResponseHandler($webservice);
        $errorHandler = $this->createErrorHandler($webservice, $webConfig->monitoring, $logger);

        return new WebApp(
            $this->httpServiceFactory->createWebApplication(),
            new Bootstrap\MiddlewarePipeline($webservice, $middlewareFactory),
            new Bootstrap\RouteBootstrapper($registry, $middlewareFactory, $responseHandler, $webConfig->identity),
            new Bootstrap\ErrorHandlerSetup($webConfig->monitoring, $errorHandler, $logger),
        );
    }

    private function createMiddlewareFactory(
        WebConfig $webConfig,
        AuthConfig $authConfig,
        LoggerInterface $logger,
    ): MiddlewareFactory {
        return new MiddlewareFactory(
            $webConfig,
            $authConfig,
            $this->middlewareRepository,
            $this->responseFactory,
            $this->rateLimitingService,
            $logger,
        );
    }

    private function createResponseHandler(Webservice $webservice): ResponseHandler
    {
        return $this->httpServiceFactory->createResponseHandler($webservice);
    }

    private function createErrorHandler(
        Webservice $webservice,
        ServiceMonitoring $monitoring,
        LoggerInterface $logger,
    ): ErrorHandler {
        return $this->httpServiceFactory->createErrorHandler(
            $webservice,
            $monitoring,
            $logger,
            $this->responseFactory
        );
    }
}
