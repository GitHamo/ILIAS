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

use InvalidArgumentException;

readonly class ActivityNamespace
{
    // requires a namespace to have at least three parts separated by backslashes (e.g., Vendor\Component\ActivityName).
    private const string PROPER_NAME_REGEXP = "/\w+([\\\\]\w+){2,}/";

    public static function create(string $className): self
    {
        if (!preg_match(self::PROPER_NAME_REGEXP, $className)) {
            throw new InvalidArgumentException(
                "{$className} is not a proper name for a dependency."
            );
        }

        $parts = explode('\\', $className);

        $vendor = $parts[0];
        $component = $parts[1];
        $name = end($parts);

        return new self(
            $vendor,
            $component,
            $name,
        );
    }

    private function __construct(
        private string $vendor,
        private string $component,
        private string $name,
    ) {}

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getPath(): string
    {
        $subject = ucfirst($this->name);
        $path = strtolower("/{$this->vendor}/{$this->component}/");
        $path .= str_replace('Query', '', $subject);

        return $path;
    }
}
