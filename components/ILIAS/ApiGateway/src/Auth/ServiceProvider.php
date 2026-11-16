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

namespace ILIAS\ApiGateway\Auth;

use ILIAS\ApiGateway\Application\Bootstrap\ServiceProvider as BaseServiceProvider;
use ILIAS\ApiGateway\Application\Factories\HttpConfigFactory;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseRefreshTokenRepository;
use ILIAS\ApiGateway\Auth\Infrastructure\DatabaseUserRepository;
use ILIAS\ApiGateway\Application\Middlewares\AuthenticationMiddleware;
use ILIAS\Database\Connection;
use Psr\Http\Server\MiddlewareInterface;
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
        $internal[UserRepository::class] = static fn(): UserRepository => new DatabaseUserRepository($pull[Connection::class]);

        $internal[Authentication::class] = static fn(): Authentication =>
        new Infrastructure\AuthService(
            new Infrastructure\JwtService(
                $internal[HttpConfigFactory::class],
            ),
            $internal[UserRepository::class],
            new DatabaseRefreshTokenRepository($pull[Connection::class]),
            $internal[HttpConfigFactory::class],
        );

        $contribute[MiddlewareInterface::class] = static fn(): MiddlewareInterface =>
        new AuthenticationMiddleware(
            $internal[Authentication::class],
        );
    }
}
