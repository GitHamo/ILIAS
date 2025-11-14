<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use ILIAS\ApiGateway\Contracts\ServiceProtocol;
use ILIAS\ApiGateway\Contracts\WebConfig;
use PHPUnit\Framework\TestCase;

class WebConfigTest extends TestCase
{
    private WebConfig $config;
    private string $baseUrl;
    private ServiceProtocol $protocol;
    private bool $isEnabled;
    private bool $debugMode;
    private bool $logErrors;
    private bool $logErrorDetails;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new WebConfig(
            $this->baseUrl = 'http://localhost',
            $this->protocol = ServiceProtocol::REST,
            $this->isEnabled = true,
            $this->debugMode = false,
            $this->logErrors = true,
            $this->logErrorDetails = false,
        );
    }

    public function testHasAccessorToBaseUrl(): void
    {
        $actual = $this->config->getBaseUrl();

        self::assertSame($this->baseUrl, $actual);
    }

    public function testHasAccessorToBasePath(): void
    {
        $actual = $this->config->getBasePath();

        self::assertSame($this->protocol->value, $actual);
    }

    public function testHasAccessorToProtocol(): void
    {
        $actual = $this->config->getProtocol();

        self::assertSame($this->protocol, $actual);
    }

    public function testHasAccessorToIsEnabled(): void
    {
        $actual = $this->config->isEnabled();

        self::assertSame($this->isEnabled, $actual);
    }

    public function testHasAccessorToIsDebugMode(): void
    {
        $actual = $this->config->isDebugMode();

        self::assertSame($this->debugMode, $actual);
    }

    public function testHasAccessorToIsLogErrors(): void
    {
        $actual = $this->config->isLogErrors();

        self::assertSame($this->logErrors, $actual);
    }

    public function testHasAccessorToIsLogErrorDetails(): void
    {
        $actual = $this->config->isLogErrorDetails();

        self::assertSame($this->logErrorDetails, $actual);
    }
}
