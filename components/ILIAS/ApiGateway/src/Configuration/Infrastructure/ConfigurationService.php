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

namespace ILIAS\ApiGateway\Configuration\Infrastructure;

use ILIAS\ApiGateway\Configuration\Domain\Configuration;
use ILIAS\ApiGateway\Configuration\Domain\Enum\EncryptionAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\HashAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\SystemSetting;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceAccess;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceIdentity;
use ILIAS\ApiGateway\Configuration\Domain\Model\ServiceMonitoring;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Configuration\Domain\SystemSettingRepository;
use ILIAS\ApiGateway\Configuration\Infrastructure\Repository\IniSettings;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;
use Override;

final readonly class ConfigurationService implements Configuration
{
    public const string DEFAULT_BASE_URL = '/';
    public const string DEFAULT_CLIENT_ID = 'ILIAS';
    public const EncryptionAlgo DEFAULT_ALGO_ENCRYPT = EncryptionAlgo::HS256;
    public const HashAlgo DEFAULT_ALGO_HASH = HashAlgo::SHA256;
    public const int DEFAULT_ACCESS_TOKEN_EXPIRE_IN = 86400; // 1 day
    public const int DEFAULT_REFRESH_TOKEN_EXPIRE_IN = 604800; // 7 days
    public const bool DEFAULT_IS_WEBSERVICE_ENABLED = false;
    public const bool DEFAULT_IS_WEBSERVICE_DOCS_ENABLED = false;
    public const bool DEFAULT_IS_DEBUG_ENABLED = false;
    public const bool DEFAULT_IS_LOGGING_ENABLED = true;
    public const bool DEFAULT_IS_LOGGING_DETAILS_ENABLED = false;

    public function __construct(
        private SystemSettingRepository $adminSettings,
        private IniSettings $iniSettings,
    ) {
    }

    #[Override]
    public function getAuthConfig(): AuthConfig
    {
        return new AuthConfig(
            issuer: $this->getClientId(),
            secretKey: $this->getSecretKey(),
            encryptionAlgo: $this->getEncryptionAlgo(),
            hashAlgo: $this->getHashAlgo(),
            accessTokenExpiry: $this->getAccessTokenExpiry(),
            refreshTokenExpiry: $this->getRefreshTokenExpiry(),
        );
    }

    #[Override]
    public function getWebConfig(ServiceProtocol $protocol): WebConfig
    {
        return new WebConfig(
            identity: new ServiceIdentity(
                protocol: $protocol,
                baseUrl: $this->getBaseUrl(),
                basePath: $protocol->value,
            ),
            access: new ServiceAccess(
                isEnabled: $this->isEnabled($protocol),
                isDocsEnabled: $this->isDocsEnabled($protocol),
                rateLimitUnits: $protocol->getRateLimitUnitsPerRequest(),
                rateLimitQuota: $this->getRateLimitQuotaLimit($protocol),
                rateLimitWindow: $protocol->getRateLimitWindow(),
            ),
            monitoring: new ServiceMonitoring(
                isDebugEnabled: $this->isDebugEnabled(),
                isLoggingEnabled: $this->isLoggingEnabled(),
                isLoggingDetailsEnabled: $this->isLoggingDetailsEnabled(),
            ),
        );
    }

    private function getBaseUrl(): string
    {
        $value = $this->iniSettings->getHttpPath();
        $value = trim($value);

        return $value === '' || $value === '0' ? self::DEFAULT_BASE_URL : $value;
    }

    private function getClientId(): string
    {
        return $this->iniSettings->getClientId();
    }

    private function getSecretKey(): string
    {
        return $this->adminSettings->get(SystemSetting::AUTH_SECRET_KEY)->asString();
    }

    private function getEncryptionAlgo(): EncryptionAlgo
    {
        $settingValue = $this->adminSettings->get(SystemSetting::AUTH_ALGO_ENCRYPTION)->asString();

        return EncryptionAlgo::tryFrom($settingValue) ?? self::DEFAULT_ALGO_ENCRYPT;
    }

    private function getHashAlgo(): HashAlgo
    {
        $settingValue = $this->adminSettings->get(SystemSetting::AUTH_ALGO_HASH)->asString();

        return HashAlgo::tryFrom($settingValue) ?? self::DEFAULT_ALGO_HASH;
    }

    private function getAccessTokenExpiry(): int
    {
        $value = $this->adminSettings->get(SystemSetting::AUTH_TOKEN_EXPIRY_ACCESS)->asInt();

        return $value > 0 ? $value : self::DEFAULT_ACCESS_TOKEN_EXPIRE_IN;
    }

    private function getRefreshTokenExpiry(): int
    {
        $value = $this->adminSettings->get(SystemSetting::AUTH_TOKEN_EXPIRY_REFRESH)->asInt();

        return $value > 0 ? $value : self::DEFAULT_REFRESH_TOKEN_EXPIRE_IN;
    }

    private function isEnabled(ServiceProtocol $protocol): bool
    {
        return match ($protocol) {
            ServiceProtocol::REST => $this->adminSettings->get(SystemSetting::REST_WS_ENABLED)->asBool(),
            default => self::DEFAULT_IS_WEBSERVICE_ENABLED,
        };
    }

    private function isDocsEnabled(ServiceProtocol $protocol): bool
    {
        return match ($protocol) {
            ServiceProtocol::REST => $this->adminSettings->get(SystemSetting::REST_DOCS_ENABLED)->asBool(),
            default => self::DEFAULT_IS_WEBSERVICE_DOCS_ENABLED,
        };
    }

    private function getRateLimitQuotaLimit(ServiceProtocol $protocol): int
    {
        return match ($protocol) {
            ServiceProtocol::REST => $this->adminSettings->get(SystemSetting::REST_RATE_LIMIT_MAX_QUOTA)->asInt(),
            default => $protocol->getRateLimitMaxQuota(),
        };
    }

    private function isDebugEnabled(): bool
    {
        return $this->iniSettings->isDebugEnabled();
    }

    private function isLoggingEnabled(): bool
    {
        return $this->iniSettings->isLoggingEnabled();
    }

    private function isLoggingDetailsEnabled(): bool
    {
        return $this->isLoggingEnabled() && $this->isDebugEnabled();
    }
}
