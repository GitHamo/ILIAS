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

namespace ILIAS\ApiGateway\Setup\Artifact;

use ILIAS\Setup\Artifact;

/**
 * An artifact that stores an OpenAPI specification as a dynamic PHP function.
 * This function allows prepending data such as base URL dynamically at runtime.
 */
class OpenApiSpecArtifact implements Artifact
{
    private const string DYNAMIC_URL_PLACEHOLDER = '___DYNAMIC_URL_PLACEHOLDER___';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data
    ) {
    }

    #[\Override]
    public function serialize(): string
    {
        $data = $this->data;

        $existingUrl = $data['servers'][0]['url'] ?? '';
        $cleanPath = ltrim((string) $existingUrl, '/');
        $exportedPath = var_export($cleanPath, true);

        if (!isset($data['servers'])) {
            $data['servers'] = [];
        }

        if (!isset($data['servers'][0])) {
            $data['servers'][0] = [];
        }

        $data['servers'][0]['url'] = self::DYNAMIC_URL_PLACEHOLDER;

        $exportedArray = var_export($data, true);

        $replacement = ($cleanPath === '')
            ? "\$url"
            : "rtrim(\$url, '/') . '/' . " . $exportedPath;

        $dynamicArrayCode = str_replace(
            "'" . self::DYNAMIC_URL_PLACEHOLDER . "'",
            $replacement,
            $exportedArray
        );

        // wrap in the functional closure
        return "<?php return function (string \$url): array {\n    return $dynamicArrayCode;\n};";
    }
}
