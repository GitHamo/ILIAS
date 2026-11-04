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

use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Application\Handlers\ResponseHandler;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\HTTP\Response\ResponseFactory;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;

/**
 * This class encapsulates some dependencies creation to help in testing.
 */
readonly class HttpServiceFactory
{
    public function createResponseHandler(Webservice $webservice): ResponseHandler
    {
        return new ResponseHandler($webservice);
    }

    public function createErrorHandler(
        Webservice $webservice,
        ServiceMonitoring $monitoring,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
    ): ErrorHandler {
        return new ErrorHandler(
            $webservice,
            $monitoring,
            $logger,
            $responseFactory,
        );
    }

    /**
     * @return SlimApp<\Psr\Container\ContainerInterface>
     * @psalm-suppress InvalidReturnType
     */
    public function createWebApplication(): SlimApp
    {
        /**
         * @psalm-suppress InvalidReturnStatement
         * @phpstan-ignore-next-line PHPStan false positive about null return
         */
        return AppFactory::create();
    }
}
