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

namespace ILIAS\ApiGateway;

use function defined;
use ILIAS\ApiGateway\Application\WebAppFactory;
use ILIAS\ApiGateway\Contracts\ServiceProtocol;
use ILIAS\ApiGateway\Contracts\WebConfig;
use ILIAS\Component\EntryPoint;

class RestAppEntryPoint extends EntryPoint\Base
{
    private const string DEFAULT_BASE_URL = 'http://localhost:8000/';

    public function __construct(
        protected WebAppFactory $webAppFactory,
    ) {
        parent::__construct(self::class);
    }

    #[\Override]
    public function enter(): int
    {
        $baseUrl = defined('ILIAS_HTTP_PATH')
            ? rtrim(ILIAS_HTTP_PATH, '/') . '/'
            : self::DEFAULT_BASE_URL;
        $isDebugEnabled = defined('DEVMODE')
            ? (bool) DEVMODE
            : false;
            
        $isDebugEnabled = true;
        // These would typically come from ILIAS settings or environment variables.
        $logErrors = true; // Always log errors in an API Gateway
        $logErrorDetails = $isDebugEnabled; // Log details only in debug mode
        $isWsEnabled = $isDebugEnabled; // @todo: for demo purposes only, remove on production

        $config = new WebConfig(
            $baseUrl,
            ServiceProtocol::REST,
            $isWsEnabled,
            $isDebugEnabled,
            $logErrors,
            $logErrorDetails,
        );

        $this->webAppFactory->create($config)->run();

        return 0;
    }
}
