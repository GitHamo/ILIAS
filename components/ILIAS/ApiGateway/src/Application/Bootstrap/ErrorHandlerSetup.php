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

namespace ILIAS\ApiGateway\Application\Bootstrap;

use ILIAS\ApiGateway\Application\Handlers\ErrorHandler;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use Psr\Log\LoggerInterface;
use Slim\App as SlimApp;

/**
 * Configures and registers the Slim ErrorMiddleware.
 */
readonly class ErrorHandlerSetup
{
    public function __construct(
        private ServiceMonitoring $monitoring,
        private ErrorHandler $errorHandler,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SlimApp<\Psr\Container\ContainerInterface> $application
     */
    public function register(SlimApp $application): void
    {
        $errorMiddleware = $application->addErrorMiddleware(
            $this->monitoring->isDebugEnabled,
            $this->monitoring->isLoggingEnabled,
            $this->monitoring->isLoggingDetailsEnabled,
            $this->logger,
        );

        $errorMiddleware->setDefaultErrorHandler($this->errorHandler);
    }
}
