<?php

declare(strict_types=1);

namespace ILIAS\ApiGateway\Configuration;

readonly class WebConfig
{
    public function __construct(
        public string $baseUrl,
        public string $basePath,
        public bool $isEnabled,
        public bool $debugMode,
        public bool $logErrors,
        public bool $logErrorDetails,
    ) {}
}
