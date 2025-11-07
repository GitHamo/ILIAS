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

namespace ILIAS\User;

use ILIAS\User\Profile\PersonalProfileGUI;
use ILIAS\User\Profile\PublicProfileGUI;
use ILIAS\StaticURL\Handler\Handler;
use ILIAS\StaticURL\Request\Request;
use ILIAS\StaticURL\Context;
use ILIAS\StaticURL\Response\Response;
use ILIAS\StaticURL\Response\Factory;
use ILIAS\StaticURL\Handler\BaseHandler;
use ILIAS\StaticURL\Builder\StandardURIBuilder;
use ILIAS\StaticURL\StaticURLConfig;
use ILIAS\Data\ReferenceId;

class StaticURLHandler extends BaseHandler implements Handler
{
    public const NAMESPACE = 'usr';
    public const CHANGE_EMAIL_OPERATIONS = 'email';
    public const REGISTRATION_OPERATIONS = 'registration';
    public const USERNAME_ASSIST_OPERATIONS = 'nameassist';
    public const PASSWORD_ASSIST_OPERATIONS = 'pwassist';

    public function getNamespace(): string
    {
        return self::NAMESPACE;
    }

    public function handle(
        Request $request,
        Context $context,
        Factory $response_factory
    ): Response {
        $additional_params = $request->getAdditionalParameters();

        $uri = match ($additional_params[0] ?? 'default') {
            self::CHANGE_EMAIL_OPERATIONS => $context->isUserLoggedIn()
                    ? $this->buildChangeEmailUrl($additional_params[1], $context->ctrl())
                    : $this->getLoginUrl($request, $context),
            self::REGISTRATION_OPERATIONS => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilAccountRegistrationGUI::class],
                ''
            ),
            self::USERNAME_ASSIST_OPERATIONS => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilPasswordAssistanceGUI::class],
                'showUsernameAssistanceForm'
            ),
            self::PASSWORD_ASSIST_OPERATIONS => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilPasswordAssistanceGUI::class],
                ''
            ),
            default => $this->buildProfileUrl($request->getReferenceId(), $context->ctrl())
        };

        return $response_factory->can($uri);
    }

    private function buildChangeEmailUrl(string $token, \ilCtrl $ctrl): string
    {
        $ctrl->setParameterByClass(PersonalProfileGUI::class, 'token', $token);
        $link = $ctrl->getLinkTargetByClass([\ilDashboardGUI::class, PersonalProfileGUI::class], PersonalProfileGUI::CHANGE_EMAIL_CMD);
        $ctrl->clearParameterByClass(PersonalProfileGUI::class, 'token');
        return $link;
    }

    private function getLoginUrl(
        Request $request,
        Context $context
    ): string {
        $target = (new StandardURIBuilder(new StaticURLConfig()))->buildTarget(
            $request->getNamespace(),
            $request->getReferenceId(),
            $request->getAdditionalParameters()
        );

        return '/login.php?target='
            . str_replace('/', '_', rtrim($target, '/'))
            . '&cmd=force_login&lang=' . $context->getUserLanguage();

    }

    private function buildProfileUrl(
        ReferenceId $target_user_id,
        \ilCtrl $ctrl
    ): string {
        $ctrl->setParameterByClass(PublicProfileGUI::class, 'user_id', $target_user_id->toInt());
        return $ctrl->getLinkTargetByClass([\ilPublicProfileBaseClassGUI::class, PublicProfileGUI::class], PublicProfileGUI::DEFAULT_CMD);
    }
}
