<?php

declare(strict_types=1);

namespace Tests\Unit\Webservice;

use ILIAS\ApiGateway\Webservice\Infrastructure\WebserviceLogger;
use ILIAS\ApiGateway\Webservice\WebserviceLoggerFactory;
use ilLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(WebserviceLoggerFactory::class)]

class WebserviceLoggerFactoryTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
    }

    public function testThrowsExceptionWhenNoLoggerAvailable(): void
    {
        $factory = new WebserviceLoggerFactory();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("No logger available");

        $factory->create('test');
    }

    public function testCreatesLogger(): void
    {
        $loggerMock = $this->createMock(ilLogger::class);

        $factory = new readonly class ($loggerMock) extends WebserviceLoggerFactory {
            public function __construct(private ilLogger $logger)
            {
            }

            protected function logger(?string $name = null): ilLogger
            {
                return $this->logger;
            }
        };

        $expected = new WebserviceLogger($loggerMock);

        $actual = $factory->create('foo');

        $this->assertEquals($expected, $actual);

        $this->assertInstanceOf(LoggerInterface::class, $actual);
        $this->assertInstanceOf(WebserviceLogger::class, $actual);
    }
}
