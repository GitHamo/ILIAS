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

final class ilApiGatewaySettings
{
    private const string SETTING_REST_WS_ENABLED = 'rest_ws_enabled';
    private const string SETTING_REST_DOCS_ENABLED = 'rest_docs_enabled';

    protected static $instance = null;
    private ilSetting $settings;

    /** @var array<string, mixed> */
    private array $settings_data = [
        self::SETTING_REST_WS_ENABLED => '0',
        self::SETTING_REST_DOCS_ENABLED => '1',
    ];

    private function __construct()
    {
        $this->settings = new ilSetting('apigateway');

        $this->read();
    }

    public static function getInstance(): ilApiGatewaySettings
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

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
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
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

            if(is_bool($value)) {
                $a_val = $value ? '1' : '0';
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
