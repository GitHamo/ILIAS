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

namespace ILIAS;

use ILIAS\ApiGateway\Application;
use ILIAS\ApiGateway\Auth;
use ILIAS\ApiGateway\Configuration;
use ILIAS\ApiGateway\Routing;
use ILIAS\Language\ComponentTranslation\ComponentLanguageFileDirectory;
use ILIAS\Language\ComponentTranslation\LanguageFileDirectory;
use ILIAS\Setup;

/**
 * Main component class for the API Gateway.
 * It coordinates modular service providers during the ILIAS component lifecycle.
 */
class ApiGateway implements Component\Component
{
    #[\Override]
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        $contribute[Setup\Agent::class] = fn() =>
        new ApiGateway\Setup\ilApiGatewaySetupAgent(
            $pull[Refinery\Factory::class],
            $internal[Application\Factories\HttpConfigFactory::class],
            $internal[Application\Factories\RoutesRegistryFactory::class],
        );

        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\OfComponent =>
        new Component\Resource\Endpoint($this, "rest/index.php", "rest");

        $contribute[Component\Resource\PublicAsset::class] = fn(): Component\Resource\OfComponent =>
        new Component\Resource\OfComponent($this, "rest/.htaccess", "rest");

        $contribute[LanguageFileDirectory::class] = fn(): LanguageFileDirectory =>
        new ComponentLanguageFileDirectory($this, 'apig');

        $providers = [
            new Configuration\ServiceProvider(),
            new Auth\ServiceProvider(),
            new Routing\ServiceProvider(),
            new Application\ServiceProvider($this),
        ];

        $register = static function (Application\Bootstrap\ServiceProvider $provider) use (
            &$define,
            &$implement,
            &$use,
            &$contribute,
            &$seek,
            &$provide,
            &$pull,
            &$internal,
        ): void {
            $provider->register(
                $define,
                $implement,
                $use,
                $contribute,
                $seek,
                $provide,
                $pull,
                $internal,
            );
        };

        array_walk($providers, $register);
    }
}
