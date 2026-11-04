<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Factories;

use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ILIAS\ApiGateway\Configuration\Domain\Configuration;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(HttpConfigFactory::class)]
final class HttpConfigFactoryTest extends TestCase
{
    private HttpConfigFactory $factory;
    private Configuration&MockObject $configuration;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new HttpConfigFactory(
            $this->configuration = $this->createMock(Configuration::class),
        );
    }

    public function testUsesComponentsToReturnInstanceOfAuthConfig(): void
    {
        $expected = $this->createMock(AuthConfig::class);

        $this->configuration->expects(self::once())
            ->method('getAuthConfig')
            ->willReturn($expected);

        $actual = $this->factory->createAuthConfig();

        self::assertSame($expected, $actual);
    }

    public function testReturnsSameAuthConfigInstance(): void
    {
        $expected = $this->createMock(AuthConfig::class);

        $this->configuration->expects(self::once())
            ->method('getAuthConfig')
            ->willReturn($expected);

        $firstInstance = $this->factory->createAuthConfig();
        $secondInstance = $this->factory->createAuthConfig();

        self::assertSame($expected, $firstInstance);
        self::assertSame($expected, $secondInstance);
    }

    public function testUsesComponentsToReturnInstanceOfWebConfig(): void
    {
        $protocol = ServiceProtocol::REST;
        $identity = new ServiceIdentity($protocol, 'https://localhost', $protocol->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $monitoring = new ServiceMonitoring(true, true, true);
        $expected = new WebConfig($identity, $access, $monitoring);

        $this->configuration->expects(self::once())
            ->method('getWebConfig')
            ->with(self::identicalTo($protocol))
            ->willReturn($expected);

        $actual = $this->factory->createWebConfig($protocol);

        self::assertSame($expected, $actual);
    }

    public function testReturnsSameWebConfigInstance(): void
    {
        $protocol = ServiceProtocol::REST;
        $identity = new ServiceIdentity($protocol, 'https://localhost', $protocol->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $monitoring = new ServiceMonitoring(true, true, true);
        $expected = new WebConfig($identity, $access, $monitoring);

        $this->configuration->expects(self::once())
            ->method('getWebConfig')
            ->with(self::identicalTo($protocol))
            ->willReturn($expected);

        $firstInstance = $this->factory->createWebConfig($protocol);
        $secondInstance = $this->factory->createWebConfig($protocol);

        self::assertSame($expected, $firstInstance);
        self::assertSame($expected, $secondInstance);
    }
}
