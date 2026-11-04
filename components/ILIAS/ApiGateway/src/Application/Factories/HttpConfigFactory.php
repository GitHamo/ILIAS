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

namespace ILIAS\ApiGateway\Application\Factories;

use ILIAS\ApiGateway\Configuration\Domain\Configuration;
use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;

class HttpConfigFactory
{
    private ?AuthConfig $authConfig = null;
    /** @var WebConfig[] */
    private array $webConfigs = [];

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function createAuthConfig(): AuthConfig
    {
        if (!$this->authConfig instanceof AuthConfig) {
            $this->authConfig = $this->configuration->getAuthConfig();
        }

        return $this->authConfig;
    }

    public function createWebConfig(ServiceProtocol $protocol): WebConfig
    {
        if (!\array_key_exists($protocol->value, $this->webConfigs)) {
            $this->webConfigs[$protocol->value] = $this->configuration->getWebConfig($protocol);
        }

        return $this->webConfigs[$protocol->value];
    }
}
