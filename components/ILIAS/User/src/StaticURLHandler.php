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
use ILIAS\LegalDocuments\Conductor as LegalDocumentsConductor;
use ILIAS\StaticURL\Handler\Handler;
use ILIAS\StaticURL\Request\Request;
use ILIAS\StaticURL\Context;
use ILIAS\StaticURL\Response\Response;
use ILIAS\StaticURL\Response\Factory;
use ILIAS\StaticURL\Handler\BaseHandler;
use ILIAS\StaticURL\Builder\StandardURIBuilder;
use ILIAS\StaticURL\StaticURLConfig;

class StaticURLHandler extends BaseHandler implements Handler
{
    public const NAMESPACE = 'usr';
    public const CHANGE_EMAIL_OPERATION = 'email';
    public const REGISTRATION_OPERATION = 'registration';
    public const USERNAME_ASSIST_OPERATION = 'nameassist';
    public const PASSWORD_ASSIST_OPERATION = 'pwassist';
    public const CONTACT_APPROVE_OPERATION = '_contact_approved';
    public const CONTACT_IGNORE_OPERATION = '_contact_ignored';

    private readonly LegalDocumentsConductor $legal_documents;

    public function __construct()
    {
        global $DIC;
        $this->legal_documents = $DIC['legalDocuments'];
    }

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
            self::CHANGE_EMAIL_OPERATION => $context->isUserLoggedIn()
                    ? $this->buildChangeEmailUrl($additional_params[1], $context->ctrl())
                    : $this->getLoginUrl($request, $context),
            self::REGISTRATION_OPERATION => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilAccountRegistrationGUI::class],
                ''
            ),
            self::USERNAME_ASSIST_OPERATION => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilPasswordAssistanceGUI::class],
                'showUsernameAssistanceForm'
            ),
            self::PASSWORD_ASSIST_OPERATION => $context->ctrl()->redirectByClass(
                [\ilStartUpGUI::class, \ilPasswordAssistanceGUI::class],
                ''
            ),
            self::CONTACT_APPROVE_OPERATION => $this->buildProfileUrl(
                $request->getReferenceId(),
                $context->ctrl(),
                'approveContactRequest'
            ),
            self::CONTACT_IGNORE_OPERATION => $this->buildProfileUrl(
                $request->getReferenceId(),
                $context->ctrl(),
                'ignoreContactRequest'
            ),
            default => $this->getRedirectToLegalDocumentsOrProfile($request, $context)
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

    private function getRedirectToLegalDocumentsOrProfile(
        Request $request,
        Context $context
    ): string {
        $user_id_as_ref_id = $request->getReferenceId();

        if ($user_id_as_ref_id !== null) {
            return $this->buildProfileUrl(
                $user_id_as_ref_id->toInt(),
                $context->ctrl(),
                PublicProfileGUI::DEFAULT_CMD
            );
        }

        $legal_documents_target = $this->legal_documents->findGotoLink(
            $request->getAdditionalParameters()[0] ?? 'default'
        );
        if ($legal_documents_target->isOK()) {
            $context->ctrl()->setTargetScript('ilias.php');
            foreach ($legal_documents_target->value()->queryParams() as $key => $value) {
                $context->ctrl()->setParameterByClass(
                    $legal_documents_target->value()->guiName(),
                    (string) $key,
                    $value
                );
            }
            return $context->ctrl()->getLinkTargetByClass(
                $legal_documents_target->value()->guiPath(),
                $legal_documents_target->value()->command()
            );
        }

        return $context->ctrl()->getLinkTargetByClass(
            [\ilDashboardGUI::class],
            'jumpToProfile'
        );
    }

    private function buildProfileUrl(
        int $target_user_id,
        \ilCtrl $ctrl,
        string $cmd
    ): string {
        $ctrl->setParameterByClass(PublicProfileGUI::class, 'user_id', $target_user_id);
        return $ctrl->getLinkTargetByClass([\ilPublicProfileBaseClassGUI::class, PublicProfileGUI::class], $cmd);
    }
}
