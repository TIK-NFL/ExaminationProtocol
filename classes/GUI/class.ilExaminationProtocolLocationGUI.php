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
 * @ilCtrl_isCalledBy ilExaminationProtocolLocationGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolLocationGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolLocationGUI extends ilExaminationProtocolBaseController
{
    private ilExaminationProtocolLocationTableGUI $location_table;

    /**
     * @throws ilCtrlException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::LOCATION_TAB_ID);
        $this->location_table = new ilExaminationProtocolLocationTableGUI($this, self::CMD_SHOW, "", $this->protocol_has_entries);
        $this->buildToolbar();

        // load from database
        $locations = $this->db_connector->getAllLocationsByProtocolID($this->protocol_id);
        $this->location_table->setData($locations);
        $this->tpl->setContent($this->location_table->getHTML());
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            default:
            case self::CMD_SHOW:
                break;
            case self::CMD_SAVE:
                $this->saveLocation();
                break;
            case self::CMD_DELETE:
                $this->deleteLocation();
                break;
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function buildToolbar() : void
    {
        if (!$this->protocol_has_entries) {
            require_once 'Services/Form/classes/class.ilTextInputGUI.php';
            $this->toolbar->setFormAction($this->ctrl->getFormAction($this, self::CMD_SAVE));
            $this->toolbar->addInputItem(new ilTextInputGUI($this->plugin->txt("location_text_title"), 'location'), true);
            $btn = $this->ui_factory->button()->standard($this->plugin->txt('add'),'');
            $this->toolbar->addComponent($btn);
        } else {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt("lock"));
        }
    }

    public function getHTML() : string
    {
        return "";
    }

    /**
     * @throws ilCtrlException
     */
    protected function deleteLocation() : void
    {
        if (!is_null($_POST['locations'])) {
            $this->db_connector->deleteLocationRows("(" . implode(",", $_POST['locations']) . ")");
            $this->ctrl->redirectByClass(self::class);
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function saveLocation() : void
    {
        $values = [
            ['integer', $this->protocol_id],
            ['text',    $_POST['location']],
        ];

        if (!in_array($_POST['location'], $this->db_connector->getAllLocationsByProtocolID($this->protocol_id))) {
            $this->db_connector->insertLocation($values);
        }
        $this->ctrl->redirectByClass(self::class);
    }
}
