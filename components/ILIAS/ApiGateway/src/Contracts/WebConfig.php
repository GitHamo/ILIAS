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

namespace ILIAS\ApiGateway\Contracts;

readonly class WebConfig
{
    public function __construct(
        private string $baseUrl,
        private ServiceProtocol $protocol,
        private bool $isEnabled,
        private bool $debugMode,
        private bool $logErrors,
        private bool $logErrorDetails,
    ) {}

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getBasePath(): string
    {
        return $this->protocol->value;
    }

    public function getProtocol(): ServiceProtocol
    {
        return $this->protocol;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function isLogErrors(): bool
    {
        return $this->logErrors;
    }

    public function isLogErrorDetails(): bool
    {
        return $this->logErrorDetails;
    }
}