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

use ILIAS\Refinery\Factory;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;

/**
 * GUI class for WebservicesSettings
 *
 * @ilCtrl_IsCalledBy ilWebservicesSettingsGUI: ilObjSystemFolderGUI
 */
class ilWebservicesSettingsGUI
{
    private const string CMD_GENERAL = 'general';
    private const string CMD_REST = 'rest';
    private const string INPUT_KEY_WS_ENABLED = 'ws_enabled';
    private const string INPUT_KEY_DOCS_ENABLED = 'docs_enabled';
    /**
     * @var array<string, string>
     */
    private const array CMD_SAVE = [
        self::CMD_REST => 'saveRestWebserviceSettings',
    ];
    private ilObjectGUI $current_obj;
    private ilObjectGUI $gui_obj;
    private ilErrorHandling $ilErr;
    private ilCtrl $ctrl;
    private ilLanguage $lng;
    private ilObjectDefinition $object_definition;
    private ilGlobalTemplateInterface $tpl;
    private ilUIService $ui_service;
    private ilRbacSystem $rbacsystem;
    private ilRbacReview $rbacreview;
    private ilRbacAdmin $rbacadmin;
    private ilObjectDataCache $objectDataCache;
    private ilTabsGUI $tabs;
    private GlobalHttpState $http;
    private Factory $refinery;
    private ilToolbarGUI $toolbar;
    private UIFactory $ui_factory;
    private UIRenderer $ui_renderer;
    private DataFactory $data_factory;
    private ilDBInterface $db;
    private ilObjUser $user;
    private ilTree $tree;
    private ilApiGatewaySettings $settingsRepo;

    public function __construct(ilObjectGUI $a_gui_obj)
    {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;

        $this->object_definition = $DIC['objDefinition'];
        $this->ui_service = $DIC->uiService();
        $this->objectDataCache = $DIC['ilObjDataCache'];
        $this->tpl = $DIC['tpl'];
        $this->lng = $DIC['lng'];
        $this->ctrl = $DIC['ilCtrl'];
        $this->rbacsystem = $DIC['rbacsystem'];
        $this->rbacreview = $DIC['rbacreview'];
        $this->rbacadmin = $DIC['rbacadmin'];
        $this->tabs = $DIC['ilTabs'];
        $this->ilErr = $DIC['ilErr'];
        $this->http = $DIC['http'];
        $this->refinery = $DIC['refinery'];
        $this->toolbar = $DIC['ilToolbar'];
        $this->ui_factory = $DIC['ui.factory'];
        $this->ui_renderer = $DIC['ui.renderer'];
        $this->db = $DIC['ilDB'];
        $this->user = $DIC['ilUser'];
        $this->tree = $DIC['tree'];

        $this->data_factory = new DataFactory();

        $this->lng->loadLanguageModule('adm');
        $this->lng->loadLanguageModule('apigw');
        $this->gui_obj = $a_gui_obj;

        $this->settingsRepo = ilApiGatewaySettings::getInstance();
    }

    protected function setTabs(string $a_cmd = '')
    {
        if (empty($a_cmd)) {
            $a_cmd = self::CMD_REST;
        }

        $this->tabs->activateTab('webservices_settings');

        $this->tabs->addSubTab(
            self::CMD_REST,
            $this->lng->txt(self::CMD_REST),
            $this->ctrl->getLinkTarget($this, 'showRestWebserviceSettings')
        );

        $this->tabs->activateSubTab($a_cmd);
    }

    /**
     * Default command
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd("showRestWebserviceSettings");

        switch ($next_class) {
            default:
                $this->$cmd();
                break;
        }
    }

    public function showRestWebserviceSettings(): void
    {
        $form = $this->createWebserviceSettingsForm(self::CMD_REST);

        $this->setTabs(self::CMD_REST);
        $this->tpl->setTitle($this->lng->txt('rest_settings'));
        $this->tpl->setContent($this->ui_renderer->render($form));
    }

    public function saveRestWebserviceSettings(): void
    {
        $form = $this->createWebserviceSettingsForm(self::CMD_REST);
        $form = $form->withRequest($this->http->request());
        $data = $form->getData();

        if ($data !== null) {
            $this->settingsRepo->setData($data);
            $this->settingsRepo->save();

            $this->tpl->setOnScreenMessage('success', $this->lng->txt("msg_obj_modified"), true);
            $this->ctrl->redirect($this, "showRestWebserviceSettings");
        } else {
            $this->setTabs(self::CMD_REST);
            $this->tpl->setContent($this->ui_renderer->render($form));
        }
    }

    private function createWebserviceSettingsForm(string $a_cmd): StandardForm
    {
        $makeKey = fn(string $key): string => "{$a_cmd}_{$key}";

        $isWsEnabled = $makeKey(self::INPUT_KEY_WS_ENABLED);
        $isDocsEnabled = $makeKey(self::INPUT_KEY_DOCS_ENABLED);
        $isWsEnabledValue = (bool) $this->settingsRepo->getData($isWsEnabled) ?? false;
        $isDocsEnabledValue = (bool) $this->settingsRepo->getData($isDocsEnabled) ?? false;

        $inputs = $a_cmd === self::CMD_GENERAL ? [] : [
            $isWsEnabled => $this->ui_factory->input()->field()->checkbox(
                $this->lng->txt($isWsEnabled),
                $this->lng->txt("{$isWsEnabled}_info")
            )->withValue($isWsEnabledValue),
            $isDocsEnabled => $this->ui_factory->input()->field()->checkbox(
                $this->lng->txt($isDocsEnabled),
                $this->lng->txt("{$isDocsEnabled}_info")
            )->withValue($isDocsEnabledValue),
        ];

        if (!array_key_exists($a_cmd, self::CMD_SAVE)) {
            throw new \InvalidArgumentException("Unknown command {$a_cmd} for saving webservice settings.");
        }

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, self::CMD_SAVE[$a_cmd]),
            $inputs
        );
    }
}
