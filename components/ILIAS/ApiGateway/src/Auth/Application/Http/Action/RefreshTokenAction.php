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

namespace ILIAS\ApiGateway\Auth\Application\Http\Action;

use ILIAS\ApiGateway\Auth\Application\Exception\AuthenticationException;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Routing\ApiAction;
use InvalidArgumentException;

readonly class RefreshTokenAction extends ApiAction
{
    public function __construct(
        private Authentication $authentication,
    ) {
        parent::__construct(
            'Refresh API Token',
            '/auth/refresh',
            ['POST'],
            'Exchanges a valid refresh token for a new token set. This should be used when the access token has expired.',
            function (array $params): array {
                $refreshToken = $params['refresh_token'] ?? '';
                $refreshToken = trim($refreshToken);

                if ('' === $refreshToken) {
                    throw new InvalidArgumentException('Refresh token is missing or empty.', 400);
                }

                return $this->authentication->refreshToken($refreshToken)->toArray();
            },
        );
    }
}
