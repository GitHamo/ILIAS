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

namespace ILIAS\ApiGateway\Activity;

readonly class ActivityNamespace
{
    /** @var string[] */
    private const array CORE_VENDORS = ['ilias'];

    public function __construct(
        private string $vendor,
        private string $component,
        private string $name,
    ) {
    }

    public function getPath(): string
    {
        $vendor = strtolower($this->vendor);
        $vendor = \in_array($vendor, self::CORE_VENDORS, true) ? '' : $vendor;
        $component = strtolower($this->component);
        $subject = ucfirst($this->name);

        if (str_starts_with($subject, 'Query')) {
            $subject = substr($subject, 5); // 5 is the length of "Query"
        } elseif (str_starts_with($subject, 'Get')) {
            $subject = substr($subject, 3); // 3 is the length of "Get"
        }

        if (str_ends_with($subject, 'Activity')) {
            $subject = substr($subject, 0, -8); // 8 is the length of "Activity"
        }

        $subject = strtolower($subject);

        return '/' . implode('/', array_filter([
            $vendor,
            $component,
            $subject,
        ]));
    }
}
