<?php

declare(strict_types=1);

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
 ********************************************************************
 */

use ILIAS\Plugin\ExaminationProtocol\GUI\ilExaminationProtocolBaseController;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @version  $Id$
 * @ilCtrl_isCalledBy ilExaminationProtocolExportGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventInput
 * @ilCtrl_Calls ilExaminationProtocolExportGUI:  ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventInput
*/

class ilExaminationProtocolExportGUI extends ilExaminationProtocolBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::EXPORT_TAB_ID);
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            default:
            case self::CMD_SHOW:
                $this->buildGUI();
                break;
            case self::CMD_EXPORT:
                $this->export();
                break;
        }
    }

    private function buildGUI() : void
    {
        $this->buildToolbar();
    }

    protected function buildToolbar() : void
    {
        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->plugin->txt("create_export"), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
        $this->toolbar->addButtonInstance($btn);
    }

    public function export() : void
    {
        // TODO Just do it!
    }

    public function getHTML() : string
    {
        return "";
    }
}
