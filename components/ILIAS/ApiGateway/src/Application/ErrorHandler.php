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

use ILIAS\ApiGateway\Contracts\WebConfig;
use ILIAS\ApiGateway\Contracts\Webservice;
use ILIAS\HTTP\Response\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * This is the global, application-wide safety net, plugged into Slim's ErrorMiddleware.
 */
readonly class ErrorHandler
{
    public function __construct(
        private Webservice $service,
        private WebConfig $config,
        private LoggerInterface $logger,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
    ): ResponseInterface {
        if ($this->config->isLogErrors()) {
            $logMessage = $exception->getMessage();
            if ($this->config->isLogErrorDetails()) {
                $logMessage .= "\nStack trace:\n" . (string)$exception;
            }

            $this->logger->error($logMessage);
        }

        $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 600
            ? (int) $exception->getCode()
            : 500;

        $responsePayload = $this->service->handleError($exception);

        $response = $this->responseFactory->create();

        $response = $response->withStatus($statusCode);

        foreach ($responsePayload->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $response->getBody()->write($responsePayload->getBody());

        return $response;
    }
}
