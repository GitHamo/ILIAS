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

namespace ILIAS\ApiGateway\Application\Middlewares;

use ILIAS\ApiGateway\Application\RequestAttributes;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\Data\UUID\Factory as UuidFactory;
use ILIAS\HTTP\StatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

/**
 * Records information about incoming requests and their outcomes
 * for monitoring and auditing purposes.
 */
final readonly class RequestLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceMonitoring $monitoring,
        private LoggerInterface $logger,
        private UuidFactory $uuidFactory,
    ) {
    }

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        $startTime = microtime(true);

        $correlationId = $this->getCorrelationId($request);

        $request = $request->withAttribute(RequestAttributes::CORRELATION_ID, $correlationId);

        $response = $handler->handle($request);

        $response = $response->withHeader('X-Correlation-ID', $correlationId);

        if (false === $this->monitoring->isLoggingEnabled) {
            return $response;
        }

        $executionTimeMs = round((microtime(true) - $startTime) * 1000.0, 2);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $statusCode = $response->getStatusCode();

        $message = \sprintf(
            "[%s] %d %s in %.2fms",
            $method,
            $statusCode,
            $path,
            $executionTimeMs,
        );

        $context = [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'execution_time_ms' => $executionTimeMs,
            'correlation_id' => $correlationId,
        ];

        $this->logger->info($message, $context);

        return $response;
    }

    private function getCorrelationId(Request $request): string
    {
        $correlationId = $request->getHeaderLine('X-Correlation-ID');

        if ($correlationId !== '') {
            try {
                // validate correlation id as valid UUID
                $this->uuidFactory->fromString($correlationId);
            } catch (\Ramsey\Uuid\Exception\InvalidArgumentException $exception) {
                throw new \InvalidArgumentException(
                    "The correlation id must be a valid UUID.",
                    StatusCode::HTTP_BAD_REQUEST,
                    $exception,
                );
            }
        } else {
            $correlationId = $this->uuidFactory->uuid4AsString();
        }

        return $correlationId;
    }
}
