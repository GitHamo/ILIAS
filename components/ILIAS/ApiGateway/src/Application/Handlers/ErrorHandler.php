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

namespace ILIAS\ApiGateway\Application\Handlers;

use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\HTTP\Response\ResponseFactory;
use ILIAS\HTTP\StatusCode;
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
        private ServiceMonitoring $monitoring,
        private LoggerInterface $logger,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
    ): ResponseInterface {
        if ($this->monitoring->isLoggingEnabled) {
            $logMessage = $exception->getMessage();
            if ($this->monitoring->isLoggingDetailsEnabled) {
                $logMessage .= "\nStack trace:\n{$exception}";
            }

            $this->logger->error($logMessage);
        }

        $exceptionStatusCode = $exception->getCode();

        $statusCode = is_numeric($exceptionStatusCode) ? (int) $exceptionStatusCode : StatusCode::HTTP_INTERNAL_SERVER_ERROR;
        $statusCode = $statusCode >= 400 && $statusCode < 600
            ? $statusCode
            : StatusCode::HTTP_INTERNAL_SERVER_ERROR;

        $responsePayload = $this->service->transformError($exception);

        $response = $this->responseFactory->create();

        foreach ($responsePayload->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $response = $response->withStatus($statusCode);

        $response->getBody()->write($responsePayload->getBody());
        $response->getBody()->rewind();

        return $response;
    }
}
