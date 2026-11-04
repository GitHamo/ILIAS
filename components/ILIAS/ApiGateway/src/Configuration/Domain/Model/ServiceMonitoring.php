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
 * Service Observation & Tracking
 *
 * Handles the "Mirror" of the service. Determines how much internal information
 * is captured in logs or exposed via debug outputs when things go wrong.
 */
final readonly class ServiceMonitoring
{
    public function __construct(
        public bool $isDebugEnabled,
        public bool $isLoggingEnabled,
        public bool $isLoggingDetailsEnabled,
    ) {
    }
}
