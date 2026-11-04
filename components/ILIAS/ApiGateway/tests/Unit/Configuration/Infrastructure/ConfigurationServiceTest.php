<?php

declare(strict_types=1);

namespace Tests\Unit\Configuration\Infrastructure;

use ILIAS\ApiGateway\Configuration\Domain\Enum\EncryptionAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\HashAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\Setting;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Configuration\Domain\SystemSettingRepository;
use ILIAS\ApiGateway\Configuration\Infrastructure\ConfigurationService;
use ILIAS\ApiGateway\Configuration\Infrastructure\Repository\IniSettings;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationService::class)]
class ConfigurationServiceTest extends TestCase
{
    private MockObject&SystemSettingRepository $systemSettingRepositoryMock;
    private MockObject&IniSettings $iniSettingsMock;
    private ConfigurationService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->systemSettingRepositoryMock = $this->createMock(SystemSettingRepository::class);
        $this->iniSettingsMock = $this->createMock(IniSettings::class);
        $this->service = new ConfigurationService(
            $this->systemSettingRepositoryMock,
            $this->iniSettingsMock
        );
    }

    public function testCreatesInstanceOfAuthConfig(): void
    {
        $clientId = 'foo';
        $this->iniSettingsMock->method('getClientId')->willReturn($clientId);

        $expected = new AuthConfig(
            issuer: $clientId,
            secretKey: $secretKey = 'foo',
            encryptionAlgo: $encryptionAlgo = EncryptionAlgo::HS256,
            hashAlgo: $hashAlgo = HashAlgo::SHA256,
            accessTokenExpiry: $accessTokenExpiry = 1234,
            refreshTokenExpiry: $refreshTokenExpiry = 5678,
        );

        $this->systemSettingRepositoryMock->expects($this->exactly(5))
            ->method('get')->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asString' => $secretKey]),
                $this->createConfiguredMock(Setting::class, ['asString' => $encryptionAlgo->value]),
                $this->createConfiguredMock(Setting::class, ['asString' => $hashAlgo->value]),
                $this->createConfiguredMock(Setting::class, ['asInt' => $accessTokenExpiry]),
                $this->createConfiguredMock(Setting::class, ['asInt' => $refreshTokenExpiry]),
            );

        $actual = $this->service->getAuthConfig();

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfAuthConfigWithInvalidAccessTokenExpiry(): void
    {
        $this->iniSettingsMock->method('getClientId')->willReturn('foo');

        $this->systemSettingRepositoryMock->expects($this->exactly(5))
            ->method('get')->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asString' => 'foo']),
                $this->createConfiguredMock(Setting::class, ['asString' => EncryptionAlgo::HS256->value]),
                $this->createConfiguredMock(Setting::class, ['asString' => HashAlgo::SHA256->value]),
                $this->createConfiguredMock(Setting::class, ['asInt' => -1]),
                $this->createConfiguredMock(Setting::class, ['asInt' => 5678]),
            );

        $actual = $this->service->getAuthConfig()->getAccessTokenExpiry();

        self::assertEquals(ConfigurationService::DEFAULT_ACCESS_TOKEN_EXPIRE_IN, $actual);
    }

    public function testCreatesInstanceOfAuthConfigWithInvalidRefreshTokenExpiry(): void
    {
        $this->iniSettingsMock->method('getClientId')->willReturn('foo');

        $this->systemSettingRepositoryMock->expects($this->exactly(5))
            ->method('get')->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asString' => 'foo']),
                $this->createConfiguredMock(Setting::class, ['asString' => EncryptionAlgo::HS256->value]),
                $this->createConfiguredMock(Setting::class, ['asString' => HashAlgo::SHA256->value]),
                $this->createConfiguredMock(Setting::class, ['asInt' => 5678]),
                $this->createConfiguredMock(Setting::class, ['asInt' => -1]),
            );

        $actual = $this->service->getAuthConfig()->getRefreshTokenExpiry();

        self::assertEquals(ConfigurationService::DEFAULT_REFRESH_TOKEN_EXPIRE_IN, $actual);
    }

    public function testCreatesInstanceOfAuthConfigWithDefaultValues(): void
    {
        $this->iniSettingsMock->method('getClientId')->willReturn(ConfigurationService::DEFAULT_CLIENT_ID);

        $expected = new AuthConfig(
            issuer: ConfigurationService::DEFAULT_CLIENT_ID,
            secretKey: $secretKey = 'foo',
            encryptionAlgo: $encryptionAlgo = ConfigurationService::DEFAULT_ALGO_ENCRYPT,
            hashAlgo: $hashAlgo = ConfigurationService::DEFAULT_ALGO_HASH,
            accessTokenExpiry: $accessTokenExpiry = ConfigurationService::DEFAULT_ACCESS_TOKEN_EXPIRE_IN,
            refreshTokenExpiry: $refreshTokenExpiry = ConfigurationService::DEFAULT_REFRESH_TOKEN_EXPIRE_IN,
        );

        $this->systemSettingRepositoryMock->expects($this->exactly(5))
            ->method('get')->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asString' => $secretKey]),
                $this->createConfiguredMock(Setting::class, ['asString' => $encryptionAlgo->value]),
                $this->createConfiguredMock(Setting::class, ['asString' => $hashAlgo->value]),
                $this->createConfiguredMock(Setting::class, ['asInt' => $accessTokenExpiry]),
                $this->createConfiguredMock(Setting::class, ['asInt' => $refreshTokenExpiry]),
            );

        $actual = $this->service->getAuthConfig();

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfWebConfig(): void
    {
        $protocol = ServiceProtocol::REST;
        $baseUrl = 'https://example.com/api';

        $this->iniSettingsMock->method('getHttpPath')->willReturn($baseUrl);
        $this->iniSettingsMock->method('isDebugEnabled')->willReturn(true);
        $this->iniSettingsMock->method('isLoggingEnabled')->willReturn(true);

        $this->systemSettingRepositoryMock->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asBool' => $isEnabled = false]),
                $this->createConfiguredMock(Setting::class, ['asBool' => $isDocsEnabled = true]),
                $this->createConfiguredMock(Setting::class, ['asInt' => $quotaLimit = 123]),
            );

        $expected = new WebConfig(
            identity: new ServiceIdentity($protocol, $baseUrl, $protocol->value),
            access: new ServiceAccess(
                isEnabled: $isEnabled,
                isDocsEnabled: $isDocsEnabled,
                rateLimitUnits: $protocol->getRateLimitUnitsPerRequest(),
                rateLimitQuota: $quotaLimit,
                rateLimitWindow: $protocol->getRateLimitWindow(),
            ),
            monitoring: new ServiceMonitoring(
                isDebugEnabled: true,
                isLoggingEnabled: true,
                isLoggingDetailsEnabled: true,
            ),
        );

        $actual = $this->service->getWebConfig($protocol);

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfWebConfigWithDevModeDisabled(): void
    {
        $protocol = ServiceProtocol::REST;

        $this->iniSettingsMock->method('getHttpPath')->willReturn('0');
        $this->iniSettingsMock->method('isDebugEnabled')->willReturn(false);
        $this->iniSettingsMock->method('isLoggingEnabled')->willReturn(false);

        $this->systemSettingRepositoryMock->expects($this->exactly(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Setting::class, ['asBool' => false]),
                $this->createConfiguredMock(Setting::class, ['asBool' => false]),
                $this->createConfiguredMock(Setting::class, ['asInt' => 0]),
            );

        $expected = new WebConfig(
            identity: new ServiceIdentity($protocol, ConfigurationService::DEFAULT_BASE_URL, $protocol->value),
            access: new ServiceAccess(
                isEnabled: false,
                isDocsEnabled: false,
                rateLimitUnits: $protocol->getRateLimitUnitsPerRequest(),
                rateLimitQuota: 0,
                rateLimitWindow: $protocol->getRateLimitWindow(),
            ),
            monitoring: new ServiceMonitoring(
                isDebugEnabled: false,
                isLoggingEnabled: false,
                isLoggingDetailsEnabled: false,
            ),
        );

        $actual = $this->service->getWebConfig($protocol);

        self::assertEquals($expected, $actual);
    }

    public function testCreatesInstanceOfWebConfigWithDefaultValues(): void
    {
        $protocol = ServiceProtocol::SOAP;

        $this->iniSettingsMock->method('getHttpPath')->willReturn('0');
        $this->iniSettingsMock->method('isDebugEnabled')->willReturn(false);
        $this->iniSettingsMock->method('isLoggingEnabled')->willReturn(ConfigurationService::DEFAULT_IS_LOGGING_ENABLED);

        $expected = new WebConfig(
            identity: new ServiceIdentity($protocol, ConfigurationService::DEFAULT_BASE_URL, $protocol->value),
            access: new ServiceAccess(
                isEnabled: ConfigurationService::DEFAULT_IS_WEBSERVICE_ENABLED,
                isDocsEnabled: ConfigurationService::DEFAULT_IS_WEBSERVICE_DOCS_ENABLED,
                rateLimitUnits: $protocol->getRateLimitUnitsPerRequest(),
                rateLimitQuota: $protocol->getRateLimitMaxQuota(),
                rateLimitWindow: $protocol->getRateLimitWindow(),
            ),
            monitoring: new ServiceMonitoring(
                isDebugEnabled: ConfigurationService::DEFAULT_IS_DEBUG_ENABLED,
                isLoggingEnabled: ConfigurationService::DEFAULT_IS_LOGGING_ENABLED,
                isLoggingDetailsEnabled: ConfigurationService::DEFAULT_IS_LOGGING_DETAILS_ENABLED,
            ),
        );

        $actual = $this->service->getWebConfig($protocol);

        self::assertEquals($expected, $actual);
    }
}
