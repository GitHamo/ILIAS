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

namespace ILIAS\ApiGateway\Webservices;

use ILIAS\ApiGateway\Models\Payload;
use ILIAS\ApiGateway\ServiceProtocol;
use ILIAS\ApiGateway\Webservice;
use Override;
use RuntimeException;

readonly class RestWebservice implements Webservice
{
    public function __construct(
        private string $basePath,
        private bool $isEnabled,
        private bool $debug,
    ) {}

    #[Override]
    public function getProtocol(): ServiceProtocol
    {
        return ServiceProtocol::REST;
    }

    #[Override]
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    #[Override]
    public function handle(Payload $payload): Payload
    {
        $jsonFlags = $this->debug ? JSON_PRETTY_PRINT : 0;

        $responseBody = json_encode([
            'success' => true,
            'data' => $payload->getData(),
        ], $jsonFlags);

        if ($responseBody === false) {
            throw new RuntimeException('Failed to encode payload');
        }

        return $payload
            ->withHeader('Content-Type', 'application/json')
            ->withBody($responseBody);
    }
}
