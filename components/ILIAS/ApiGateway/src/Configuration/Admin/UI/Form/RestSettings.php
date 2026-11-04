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

namespace ILIAS\ApiGateway\Configuration\Admin\UI\Form;

use ILIAS\ApiGateway\Configuration\Admin\UI\Form\SettingsForm;
use ILIAS\ApiGateway\Configuration\Domain\Enum\SystemSetting;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;

final readonly class RestSettings extends SettingsForm
{
    #[\Override]
    public function get(string $form_action): StandardForm
    {
        $rest_ws_enabled = SystemSetting::REST_WS_ENABLED->value;
        $rest_docs_enabled = SystemSetting::REST_DOCS_ENABLED->value;
        $rest_rate_limit_max_quota = SystemSetting::REST_RATE_LIMIT_MAX_QUOTA->value;

        $rest_ws_enabled_value = $this->settings_service->getData($rest_ws_enabled);
        $rest_docs_enabled_value = $this->settings_service->getData($rest_docs_enabled);
        $rest_rate_limit_max_quota_value = $this->settings_service->getData($rest_rate_limit_max_quota);
        $rest_rate_limit_max_quota_value = is_numeric($rest_rate_limit_max_quota_value) ? (int) $rest_rate_limit_max_quota_value : 0;

        $inputs = [
            $rest_ws_enabled => $this->ui_factory->input()->field()->checkbox(
                $this->lng->txt("{$rest_ws_enabled}_label"),
                $this->lng->txt("{$rest_ws_enabled}_info")
            )->withValue((bool) $rest_ws_enabled_value),

            $rest_docs_enabled => $this->ui_factory->input()->field()->checkbox(
                $this->lng->txt("{$rest_docs_enabled}_label"),
                $this->getDocsFieldByline($rest_docs_enabled),
            )->withValue((bool) $rest_docs_enabled_value),

            $rest_rate_limit_max_quota => $this->ui_factory->input()->field()->numeric(
                $this->lng->txt("webservice_rate_limit_max_quota_label"),
                $this->lng->txt("webservice_rate_limit_max_quota_info"),
            )->withValue($rest_rate_limit_max_quota_value),
        ];

        $ff = $this->ui_factory->input()->field();

        $settings = $ff->section(
            $inputs,
            $this->lng->txt('rest_settings'),
        );

        return $this->ui_factory->input()
            ->container()
            ->form()
            ->standard(
                $form_action,
                [$settings],
            );
    }

    private function getDocsFieldByline(string $key): string
    {
        $docs_base_url = $this->getSwaggerUrl();

        return \sprintf(
            $this->lng->txt("{$key}_info"),
            $docs_base_url,
            $docs_base_url,
        );
    }

    private function getSwaggerUrl(): string
    {
        $base_url = \defined('ILIAS_HTTP_PATH') ? ILIAS_HTTP_PATH : 'http://YOUR_ILIAS_BASE_URL';

        return "$base_url/swagger.php";
    }
}
