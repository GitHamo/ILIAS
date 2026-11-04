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

namespace ILIAS\ApiGateway\Configuration;

use ILIAS\ApiGateway\Application\Bootstrap\ServiceProvider as BaseServiceProvider;
use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ArrayAccess;

final readonly class ServiceProvider implements BaseServiceProvider
{
    /**
     * @param array|ArrayAccess<string, mixed> $define
     * @param array|ArrayAccess<string, mixed> $implement
     * @param array|ArrayAccess<string, mixed> $use
     * @param array|ArrayAccess<string, mixed> $contribute
     * @param array|ArrayAccess<string, mixed> $seek
     * @param array|ArrayAccess<string, mixed> $provide
     * @param array|ArrayAccess<string, mixed> $pull
     * @param array|ArrayAccess<string, mixed> $internal
     */
    public function register(
        array | ArrayAccess &$define,
        array | ArrayAccess &$implement,
        array | ArrayAccess &$use,
        array | ArrayAccess &$contribute,
        array | ArrayAccess &$seek,
        array | ArrayAccess &$provide,
        array | ArrayAccess &$pull,
        array | ArrayAccess &$internal,
    ): void {
        $internal[Domain\Configuration::class] = static fn(): Domain\Configuration =>
        new Infrastructure\ConfigurationService(
            new Infrastructure\Repository\AdminSettings(),
            new Infrastructure\Repository\IniSettings(),
        );

        /**
         * @var HttpConfigFactory
         *
         * workaround @todo: replace with DI then replace usage with config objects
         */
        $internal[HttpConfigFactory::class] = static fn(): HttpConfigFactory =>
        new HttpConfigFactory(
            $internal[Domain\Configuration::class],
        );
    }
}
