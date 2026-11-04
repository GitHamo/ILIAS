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

namespace ILIAS\ApiGateway\Configuration\Domain;

use ILIAS\ApiGateway\Configuration\Domain\Model\AuthConfig;
use ILIAS\ApiGateway\Configuration\Domain\Model\WebConfig;
use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;

interface Configuration
{
    public function getAuthConfig(): AuthConfig;

    public function getWebConfig(ServiceProtocol $protocol): WebConfig;
}
