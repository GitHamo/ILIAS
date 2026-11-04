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

/**
 * Admission Control & Permissions
 *
 * Acts as the "Gatekeeper" for the service. Controls lifecycle status, public
 * availability of documentation, and usage quotas.
 */
final readonly class ServiceAccess
{
    public function __construct(
        public bool $isEnabled,
        public bool $isDocsEnabled,
        public int $rateLimitUnits,
        public int $rateLimitQuota,
        public int $rateLimitWindow,
    ) {
    }
}
