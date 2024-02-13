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

use ILIAS\Plugin\ExaminationProtocol\GUI\ilExaminationProtocolTableBaseController;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolSupervisorGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolSupervisorGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolSupervisorGUI extends ilExaminationProtocolTableBaseController
{

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     * @throws ilCtrlException
     * @throws ilException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::SUPERVISOR_TAB_ID);
        $this->table = new ilExaminationProtocolSupervisorTableGUI($this, self::CMD_SHOW, '', $this->protocol_has_entries);
    }

    protected function buildGUI()
    {
        $this->buildToolbar();
        $supervisors = $this->db_connector->getAllSupervisorsByProtocolID(intval($this->protocol_id));
        $this->table->setData($supervisors);
        $table_html = $this->table->getHTML();
        $this->tpl->setContent($table_html);
        $this->tpl->printToStdout();
    }


    /**
     * @throws ilCtrlException
     */
    protected function buildToolbar(): void
    {
        if (!$this->protocol_has_entries) {
            require_once 'Services/Form/classes/class.ilTextInputGUI.php';
            $this->toolbar->setFormAction($this->ctrl->getFormAction($this, self::CMD_SAVE));
            $this->toolbar->addInputItem(new ilTextInputGUI($this->plugin->txt('supervisor_text_title'), 'name'), true);
            $btn = $this->ui_factory->button()->standard($this->plugin->txt('add'),'');
            $this->toolbar->addComponent($btn);
        } else {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('lock'));
        }
    }

    protected function deleteContent(): void
    {
        if (!is_null($_POST['supervisors'])) {
            $this->db_connector->deleteSupervisorRows("(" . implode(",", $_POST['supervisors']) . ")");
        }
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function saveContent(): void
    {
        $values = [
            ['integer', $this->protocol_id],
            ['text',    $_POST['name']],
        ];
        if (!in_array($_POST['name'], $this->db_connector->getAllSupervisorsByProtocolID(intval($this->protocol_id)))) {
            $this->db_connector->insertSupervisor($values);
        }
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function applyFilter()
    {
    }

    protected function resetFilter()
    {
    }
}
