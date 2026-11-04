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

use DateTimeImmutable;
use ILIAS\ApiGateway\Application\Exceptions\TooManyRequestsException;
use ILIAS\ApiGateway\Application\Factories\RateLimitFactory;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Middleware that limits the number of requests a client can make
 * within a specified time frame to prevent abuse.
 */
final readonly class RequestRateLimitingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceAccess $access,
        private RateLimitFactory $rateLimitFactory,
        private RateLimiting $rateLimitService,
    ) {
    }

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        $unitsPerRequest = $this->access->rateLimitUnits;
        $limit = $this->rateLimitFactory->createFromRequest($request);

        $isAllowed = $this->rateLimitService->consume(
            limit: $limit,
            units: $unitsPerRequest,
            now: new DateTimeImmutable(),
        );

        if (!$isAllowed) {
            throw new TooManyRequestsException($request);
        }

        return $handler->handle($request);
    }
}
