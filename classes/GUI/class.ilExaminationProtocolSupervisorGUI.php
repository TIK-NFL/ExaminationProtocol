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
 * @ilCtrl_isCalledBy ilExaminationProtocolSupervisorGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolSupervisorGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolSupervisorGUI extends ilExaminationProtocolBaseController
{
    /** @var ilExaminationProtocolEventTableGUI */
    private $supervisor_table;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::SUPERVISOR_TAB_ID);
        $this->buildToolbar();
        // table
        $this->supervisor_table = new ilExaminationProtocolSupervisorTableGUI($this, "show", "", $this->protocol_has_entries);
        // load from database
        $supervisors = $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id);
        $this->supervisor_table->setData($supervisors);
        $table_html = $this->supervisor_table->getHTML();
        $html = $table_html;
        $this->tpl->setContent($html);
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            default:
            case self::CMD_SHOW:
                break;
            case self::CMD_SAVE:
                $this->save();
                break;
            case self::CMD_DELETE:
                $this->delete();
                break;
        }
    }

    public function getHTML() : string
    {
        return "";
    }

    protected function buildToolbar()  : void
    {
        require_once 'Services/Form/classes/class.ilTextInputGUI.php';
        if (!$this->protocol_has_entries) {
            $this->toolbar->setFormAction($this->ctrl->getFormAction($this, self::CMD_SAVE));
            $this->toolbar->addInputItem(new ilTextInputGUI($this->plugin->txt("supervisor_text_title"), 'name'), true);
            $button = ilSubmitButton::getInstance();
            $button->setCaption($this->lng->txt('add'), false);
            $button->setCommand(self::CMD_SAVE);
            $this->toolbar->addButtonInstance($button);
        } else {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt("lock"));
        }
    }

    protected function delete() : void
    {
        if (!is_null($_POST['supervisors'])) {
            $this->db_connector->deleteSupervisorRows("(" . implode(",", $_POST['supervisors']) . ")");
            $this->ctrl->redirectByClass(self::class);
        }
    }

    protected function save() : void
    {
        // build input Array
        $values = [
            ['integer', $this->protocol_id],
            ['text',    $_POST['name']],
        ];
        // update Database
        if (!in_array($_POST['name'], $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id))) {
            $this->db_connector->insertSupervisor($values);
        }
        $this->ctrl->redirectByClass(self::class);
    }
}
