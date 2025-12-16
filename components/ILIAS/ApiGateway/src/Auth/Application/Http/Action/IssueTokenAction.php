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
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Routing\ApiAction;

readonly class IssueTokenAction extends ApiAction
{
    public function __construct(
        private Authentication $authentication,
        private UserRepository $userRepository,
    ) {
        parent::__construct(
            'AuthenticateUserCredentials',
            '/token/auth',
            ['POST'],
            'Authenticates a user and returns an access token.',
            function (array $params): array {
                $username = $params['username'] ?? '';
                $password = $params['password'] ?? '';

                $username = trim($username);
                $password = trim($password);

                if (\in_array('', [$username, $password])) {
                    throw new AuthenticationException('Username or password is empty.', 401);
                }

                $user = $this->userRepository->get($username, $password);

                return $this->authentication->createToken($user)->toArray();
            },
        );
    }
}
