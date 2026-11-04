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

namespace ILIAS\ApiGateway\Configuration\Domain\Model;

use ILIAS\ApiGateway\Webservice\Domain\Enum\ServiceProtocol;

/**
 * Service Address & Protocol
 *
 * Identifies the physical and logical presence of the service. These settings are
 * used together by the Bootstrapper and Entry Points to determine where the service
 * lives and what language it speaks.
 */
final readonly class ServiceIdentity
{
    public function __construct(
        public ServiceProtocol $protocol,
        public string $baseUrl,
        public string $basePath,
    ) {
    }
}
