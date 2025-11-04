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

use ILIAS\ApiGateway\Application\WebAppFactory;
use ILIAS\ApiGateway\Routing\ApiAction;
use ILIAS\ApiGateway\ServiceProtocol;

$settings = ilApiGatewaySettings::getInstance();
$isWsEnabled = (bool) $settings->getData('rest_ws_enabled');

$baseUrl = defined('ILIAS_HTTP_PATH')
            ? rtrim(ILIAS_HTTP_PATH, '/') . '/'
            : 'http://localhost:8000/';
$isDebugEnabled = defined('DEVMODE')
            ? (bool) DEVMODE
            : false;

// These would typically come from ILIAS settings or environment variables.
$logErrors = true; // Always log errors in an API Gateway
$logErrorDetails = $isDebugEnabled; // Log details only in debug mode
$isWsEnabled = $isDebugEnabled; // @todo: for demo purposes only, remove on production

$routes = [
    // static or extra routes
    new ApiAction(
        name: 'Ping',
        path: "/ping",
        methods: ['GET'],
        description: 'A simple ping pong route for testing purposes.',
        handler: fn(): string => 'Pong!',
    ),
];

WebAppFactory::create(
    ServiceProtocol::REST,
    $baseUrl,
    'rest',
    $isWsEnabled,
    $isDebugEnabled,
    $logErrors,
    $logErrorDetails,
    $routes,
)->run();