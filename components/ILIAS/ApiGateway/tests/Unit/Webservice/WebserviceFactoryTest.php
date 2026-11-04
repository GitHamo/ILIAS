<?php

declare(strict_types=1);

namespace Tests\Unit\Webservice;

use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use ILIAS\ApiGateway\Webservice\Domain\Webservice;
use ILIAS\ApiGateway\Webservice\Infrastructure\RestWebservice;
use ILIAS\ApiGateway\Webservice\WebserviceFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WebserviceFactoryTest extends TestCase
{
    private WebserviceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new WebserviceFactory();
    }

    public function testCreatesRestWebservice(): void
    {
        $identity = new ServiceIdentity(ServiceProtocol::REST, 'http://localhost', ServiceProtocol::REST->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $monitoring = new ServiceMonitoring(true, true, true);
        $config = new WebConfig($identity, $access, $monitoring);

        $expected = new RestWebservice($monitoring);
        $actual = $this->factory->create($config);

        $this->assertInstanceOf(RestWebservice::class, $actual);

        $this->assertEquals($expected, $actual);
    }

    /**
     * This test case is removed as soon as SOAP is supported
     */
    public function testThrowExceptionInCaseOfUnsupportedProtocol(): void
    {
        $protocol = ServiceProtocol::SOAP;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            \sprintf("Unsupported service protocol: %s", $protocol->name)
        );

        $identity = new ServiceIdentity($protocol, 'http://localhost', $protocol->value);
        $access = new ServiceAccess(true, true, 1, 100, 60);
        $monitoring = new ServiceMonitoring(true, true, true);
        $config = new WebConfig($identity, $access, $monitoring);

        $this->factory->create($config);
    }
}
