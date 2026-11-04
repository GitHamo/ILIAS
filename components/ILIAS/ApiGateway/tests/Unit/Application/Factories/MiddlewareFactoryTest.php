<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Factories\MiddlewareFactory;
use ILIAS\ApiGateway\Application\Middlewares;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Middleware\MiddlewareRepository;
use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\HTTP\Response\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(MiddlewareFactory::class)]
class MiddlewareFactoryTest extends TestCase
{
    private MiddlewareFactory $factory;
    private MockObject&MiddlewareRepository $middlewareRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $webConfig = new WebConfig(
            identity: new ServiceIdentity(ServiceProtocol::REST, 'http://localhost', ServiceProtocol::REST->value),
            access: new ServiceAccess(true, true, 1, 100, 60),
            monitoring: new ServiceMonitoring(true, true, true),
        );

        $this->factory = new MiddlewareFactory(
            $webConfig,
            $this->createMock(AuthConfig::class),
            $this->middlewareRepository = $this->createMock(MiddlewareRepository::class),
            $this->createMock(ResponseFactory::class),
            $this->createMock(RateLimiting::class),
            $this->createMock(LoggerInterface::class),
        );
    }

    public function testReturnsExistingMiddleware(): void
    {
        $expected = $this->createMock(MiddlewareInterface::class);

        $this->middlewareRepository
            ->expects(self::once())
            ->method('get')
            ->with(MiddlewareInterface::class)
            ->willReturn($expected);

        // call the create multiple times to test it is added once
        $this->factory->create(MiddlewareInterface::class);
        $this->factory->create(MiddlewareInterface::class);
        $this->factory->create(MiddlewareInterface::class);
        $this->factory->create(MiddlewareInterface::class);

        $actual = $this->factory->create(MiddlewareInterface::class);

        self::assertSame($expected, $actual);
    }

    #[DataProvider('middlewareClasses')]
    public function testCreatesInstanceOfCoreMiddleware(string $className): void
    {
        self::assertSame(
            $className,
            $this->factory->create($className)::class,
        );
    }

    /**
     * @return string[][]
     */
    public static function middlewareClasses(): array
    {
        return [
            [Middlewares\ApiServiceStatusMiddleware::class],
            [Middlewares\BrowserSecurityMiddleware::class],
            [Middlewares\CorsMiddleware::class],
            [Middlewares\JsonBodyParserMiddleware::class],
            [Middlewares\JsonContentNegotiationMiddleware::class],
            [Middlewares\RequestLoggingMiddleware::class],
            [Middlewares\RequestParameterMiddleware::class],
            [Middlewares\RequestRateLimitingMiddleware::class],
            [Middlewares\TransportSecurityMiddleware::class],
        ];
    }
}
