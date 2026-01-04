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

namespace ILIAS\ApiGateway\Configuration;

use ILIAS\ApiGateway\Configuration\Domain\Enum\EncryptionAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\HashingAlgo;
use ILIAS\ApiGateway\Configuration\Domain\Enum\SystemSetting;
use ilSetting;

/**
 * @codeCoverageIgnore To be tested when settings are loaded by DI not global
 */
final class ilApiGatewaySettings
{
    private const string MODULE_NAME = 'apigateway';
    protected static ?ilApiGatewaySettings $instance = null;
    private ilSetting $settings;

    /** @var array<string, string> */
    private array $settings_data = [
        /**
         * defaults for system settings, load when specific setting is missing or empty
         */
        SystemSetting::AUTH_SECRET_KEY->value => '', // generate randomly on installation
        SystemSetting::AUTH_ALGO_ENCRYPTION->value => EncryptionAlgo::HS256->value,
        SystemSetting::AUTH_ALGO_HASH->value => HashingAlgo::SHA256->value,
        SystemSetting::AUTH_TOKEN_EXPIRY_ACCESS->value => "86400",
        SystemSetting::AUTH_TOKEN_EXPIRY_REFRESH->value => "604800",
        SystemSetting::REST_WS_ENABLED->value => '0',
        SystemSetting::REST_DOCS_ENABLED->value => '1',
    ];

    private function __construct()
    {
        global $DIC;

        $this->settings = new ilSetting(self::MODULE_NAME, true);

        $this->read();
    }

    public static function getInstance(): ilApiGatewaySettings
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return null|string|array<string, string>
     */
    public function getData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->settings_data;
        }

        if (!isset($this->settings_data[$key])) {
            return null;
        }

        return $this->settings_data[$key];
    }

    /**
     * @param array<mixed, mixed> $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!\is_string($value) && \is_array($value)) {
                // cover form sections case where it is multidimensional arrays
                $this->setData($value);
                continue;
            }
            if (!isset($this->settings_data[$key])) {
                continue;
            }

            $this->settings_data[$key] = $value;
        }
    }

    public function save(): void
    {
        foreach ($this->settings_data as $key => $value) {
            $a_val = (string) $value;

            if (\is_bool($value)) {
                $a_val = $value ? '1' : '0';
            }

            // add special case for empty secret key to generate a new one
            if ($key === SystemSetting::AUTH_SECRET_KEY->value && $a_val === '') {
                $a_val = \ILIAS\ApiGateway\Configuration\Infrastructure\RandomKeyGenerator::generate();
            }
            $this->settings->set(
                $key,
                $a_val,
            );
        }
    }

    private function read(): void
    {
        foreach ($this->settings_data as $key => $value) {
            $this->settings_data[$key] = $this->settings->get(
                $key,
                (string) $value
            );
        }
    }
}
