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

use ILIAS\ApiGateway\Configuration\Admin\ilApiGatewaySettings;
use ILIAS\ApiGateway\Configuration\Domain\Enum\SystemSetting;
use ILIAS\ApiGateway\Setup\Objectives\BuildOpenAPISpecObjective;

require_once '../vendor/composer/vendor/autoload.php';
require_once __DIR__ . '/../artifacts/bootstrap_default.php';

entry_point('ILIAS Legacy Initialisation Adapter');

$user = $DIC->user();
$refinery = $DIC->refinery();

$is_enabled_value = ilApiGatewaySettings::getInstance()->getData(SystemSetting::REST_DOCS_ENABLED->value);
$is_enabled = $refinery->kindlyTo()->bool()->transform($is_enabled_value);

$is_guest = $user->getId() === ANONYMOUS_USER_ID;
$is_admin = $user->getId() === SYSTEM_USER_ID;
$is_active = ($is_enabled && $is_guest) || $is_admin;

if (!$is_active) {
    header('HTTP/1.1 404 Not Found');
    exit();
}

$swaggerUiVersion = '5.10.0';
$openapiDefaultData = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => 'ILIAS API Gateway',
    ],
];
$openapiPath = BuildOpenAPISpecObjective::PATH();
$openapi = file_exists($openapiPath) ? include $openapiPath : null;
$baseUrl = defined('ILIAS_HTTP_PATH') ? ILIAS_HTTP_PATH : '';
$openapiData = $openapi ? $openapi($baseUrl) : $openapiDefaultData;
$openapiJson = json_encode($openapiData, JSON_THROW_ON_ERROR);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in openapi.json - " . json_last_error_msg() . "\n";
    exit(1);
}

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$openapiData['info']['title']} - API Documentation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/{$swaggerUiVersion}/swagger-ui.min.css">
    <style>
        body { margin: 0; padding: 0; }
        .swagger-ui .topbar { display: none; }
    </style>
    <link rel="icon" href="./assets/images/logo/favicon.ico" type="image/x-icon">
</head>
<body>
    <div id="swagger-ui"></div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/{$swaggerUiVersion}/swagger-ui-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/{$swaggerUiVersion}/swagger-ui-standalone-preset.min.js"></script>
    <script>
        window.onload = function() {
            const spec = {$openapiJson};
            
            const ui = SwaggerUIBundle({
                spec: spec,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: "list",
                filter: true
            });
        }
    </script>
</body>
</html>
HTML;

echo $html;

exit();
