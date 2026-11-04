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

namespace ILIAS\ApiGateway\Application\Routes\Auth;

use ILIAS\ApiGateway\Application\Routes\ApiRoute;
use ILIAS\ApiGateway\Auth\Domain\Repository\UserRepository;
use ILIAS\ApiGateway\Auth\Domain\Service\Authentication;
use ILIAS\ApiGateway\Routing\HttpMethod;
use ILIAS\HTTP\StatusCode;
use ILIAS\Refinery;
use InvalidArgumentException;
use UnexpectedValueException;

final readonly class IssueTokenRoute extends ApiRoute
{
    public function __construct(
        private Refinery\Factory $refineryFactory,
        private Authentication $authentication,
        private UserRepository $userRepository,
    ) {
        parent::__construct(
            'Create API Token',
            'Authenticates a user and returns a new token set (access and refresh tokens).',
            '/auth/token',
            HttpMethod::POST,
            function (array $params): array {
                $stringRefinery = $this->refineryFactory->string();
                /** @var Refinery\To\Transformation\StringTransformation $stringTransformation */
                $stringTransformation = $this->refineryFactory->to()->string();
                $notEmptyConstraint = $stringRefinery->hasMinLength(1);

                try {
                    $username = $stringTransformation->transform($params['username'] ?? '');
                    $password = $stringTransformation->transform($params['password'] ?? '');

                    $notEmptyConstraint->check($username);
                    $notEmptyConstraint->check($password);
                } catch (InvalidArgumentException | UnexpectedValueException $e) {
                    throw new InvalidArgumentException('Username or password is missing, empty, or has an invalid type.', StatusCode::HTTP_BAD_REQUEST, $e);
                }

                $username = trim($username);
                $password = trim($password);

                $user = $this->userRepository->login($username, $password);

                return $this->authentication->createToken($user)->toArray();
            },
        );
    }
}
