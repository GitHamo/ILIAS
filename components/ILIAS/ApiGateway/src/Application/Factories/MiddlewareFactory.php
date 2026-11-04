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

use ILIAS\ApiGateway\Application\Middlewares;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\HTTP\Response\ResponseFactory;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

class MiddlewareFactory
{
    /**
     * @var array<string, MiddlewareInterface>
     */
    private array $instances = [];

    public function __construct(
        private readonly WebConfig $webConfig,
        private readonly AuthConfig $authConfig,
        private readonly MiddlewareRepository $middlewareRepository,
        private readonly ResponseFactory $responseFactory,
        private readonly RateLimiting $rateLimiting,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(string $className): MiddlewareInterface
    {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        $instance = match ($className) {
            Middlewares\ApiServiceStatusMiddleware::class => $this->createApiServiceStatusMiddleware(),
            Middlewares\BrowserSecurityMiddleware::class => $this->createBrowserSecurityMiddleware(),
            Middlewares\CorsMiddleware::class => $this->createCorsMiddleware(),
            Middlewares\JsonBodyParserMiddleware::class => $this->createJsonBodyParserMiddleware(),
            Middlewares\JsonContentNegotiationMiddleware::class => $this->createJsonContentNegotiationMiddleware(),
            Middlewares\RequestLoggingMiddleware::class => $this->createRequestLoggingMiddleware(),
            Middlewares\RequestParameterMiddleware::class => $this->createRequestParameterMiddleware(),
            Middlewares\RequestRateLimitingMiddleware::class => $this->createRequestRateLimitingMiddleware(),
            Middlewares\TransportSecurityMiddleware::class => $this->createTransportSecurityMiddleware(),
            default => $this->middlewareRepository->get($className),
        };

        return $this->instances[$className] = $instance;
    }

    private function createApiServiceStatusMiddleware(): MiddlewareInterface
    {
        return new Middlewares\ApiServiceStatusMiddleware(
            $this->webConfig->access,
            $this->responseFactory
        );
    }

    private function createBrowserSecurityMiddleware(): MiddlewareInterface
    {
        return new Middlewares\BrowserSecurityMiddleware();
    }

    private function createCorsMiddleware(): MiddlewareInterface
    {
        return new Middlewares\CorsMiddleware(
            $this->responseFactory,
        );
    }

    private function createJsonBodyParserMiddleware(): MiddlewareInterface
    {
        return new Middlewares\JsonBodyParserMiddleware();
    }

    private function createJsonContentNegotiationMiddleware(): MiddlewareInterface
    {
        return new Middlewares\JsonContentNegotiationMiddleware();
    }

    private function createRequestLoggingMiddleware(): MiddlewareInterface
    {
        return new Middlewares\RequestLoggingMiddleware(
            $this->webConfig->monitoring,
            $this->logger,
            new \ILIAS\Data\UUID\Factory(),
        );
    }

    private function createRequestParameterMiddleware(): MiddlewareInterface
    {
        return new Middlewares\RequestParameterMiddleware();
    }

    private function createRequestRateLimitingMiddleware(): MiddlewareInterface
    {
        return new Middlewares\RequestRateLimitingMiddleware(
            $this->webConfig->access,
            $this->createRateLimitFactory(),
            $this->rateLimiting,
        );
    }

    private function createTransportSecurityMiddleware(): MiddlewareInterface
    {
        return new Middlewares\TransportSecurityMiddleware(
            $this->webConfig->identity,
        );
    }

    private function createRateLimitFactory(): RateLimitFactory
    {
        return new RateLimitFactory(
            $this->webConfig->identity,
            $this->webConfig->access,
            $this->authConfig,
        );
    }
}
