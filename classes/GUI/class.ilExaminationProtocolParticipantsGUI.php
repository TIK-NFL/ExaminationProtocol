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
 * @ilCtrl_isCalledBy ilExaminationProtocolParticipantsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolParticipantsGUI: ilPermissionGUI, ilInfoScreenGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolParticipantsGUI extends ilExaminationProtocolBaseController
{
    private ilExaminationProtocolParticipantsTableGUI $participant_table;

    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(ilExaminationProtocolBaseController::PARTICIPANT_TAB_ID);
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            case self::CMD_DELETE:
                $this->deleteParticipant();
                $this->show();
                break;
            case "":
            case "doUserAutoComplete":
            case "addUserFromAutoComplete":
            case "performSearch":
            case "listUsers":
            case self::CMD_ADD_PARTICIPANTS:
                $rep_search = new ilRepositorySearchGUI();
                $rep_search->setCallback($this, 'addUser');
                $rep_search->setTitle($this->plugin->txt('participant_selector_title'));
                $this->ctrl->setReturn($this, self::CMD_SHOW);
                $this->ctrl->forwardCommand($rep_search);
                break;
            case 'post':
            case self::CMD_APPLY_FILTER:
                $this->applyFilter();
                break;
            case self::CMD_RESET_FILTER:
                $this->resetFilter();
                break;
            case 'addUser':
            case 'showSearchSelected':
                $this->addUser();
                $this->show();
                break;
            default:
            case self::CMD_SHOW:
                $this->show();
                break;
        }
    }

    /**
     * @param array $user_ids
     * @return void
     */
    public function addUser(array $user_ids = array()) : void
    {
        if (empty($user_ids) && !empty($_REQUEST['selected_id'])) {
            $user_ids[] = $_REQUEST['selected_id'];
        } elseif (empty($user_ids) && !empty($_REQUEST['user'])) {
            $user_ids = $_REQUEST['user'];
        }
        foreach ($user_ids as $user_id) {
            $this->saveUser($user_id);
        }
    }

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    protected function show() : void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->loadData();
        $this->tpl->setContent($this->participant_table->getHTML());
    }

    /**
     * @throws ilCtrlException
     */
    protected function buildToolbar() : void
    {
        ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array(
                'auto_complete_name' => $this->lng->txt('user'),
                'submit_name' => $this->lng->txt('add'),
                'add_from_container' => $this->test_object->test_id
            ),
            true
        );

        $btn = $this->ui_factory->button()->standard($this->plugin->txt('participant_btn_add_participants_title'),
            $this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI'));

        $this->toolbar->addComponent($btn);
    }

    protected function buildTable() : void
    {
        $this->participant_table = new ilExaminationProtocolParticipantsTableGUI($this, self::CMD_SHOW, "");
        $this->participant_table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->participant_table->setResetCommand(self::CMD_RESET_FILTER);
    }

    protected function loadData() : void
    {
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        $usr_participant_mapping = array_reduce($participants, function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, array());
        $usr_ids = array_keys($usr_participant_mapping);
        $usr_login = unserialize($_SESSION['form_texa_participant_login']) ?? "";
        $usr_name = unserialize($_SESSION['form_texa_participant_name']) ?? "";
        $usr_mrt = unserialize($_SESSION['form_texa_participant_matriculation']) ?? "";
        $data = $this->db_connector->getAllParticipantsByUserIDandFilter(
            "'" . implode("', '", $usr_ids) . "'",
            $usr_login,
            $usr_name,
            $usr_mrt
        );
        foreach ($data as $index => $entry) {
            $data[$index]['participant_id'] = $usr_participant_mapping[$entry['usr_id']];
        }
        $this->participant_table->setData($data);
    }

    /**
     * @throws ilException
     * @throws ilCtrlException
     */
    private function applyFilter() : void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->participant_table->writeFilterToSession();
        $this->participant_table->resetOffset();
        $this->loadData();
        $this->tpl->setContent($this->participant_table->getHTML());
    }

    /**
     * @throws ilException
     * @throws ilCtrlException
     */
    private function resetFilter() : void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->participant_table->resetOffset();
        $this->participant_table->resetFilter();
        $_SESSION['form_texa_participant_login'] = "";
        $_SESSION['form_texa_participant_name'] = "";
        $_SESSION['form_texa_participant_matriculation'] = "";
        $this->loadData();
        $this->tpl->setContent($this->participant_table->getHTML());
    }

    public function getHTML() : string
    {
        return "";
    }

    protected function deleteParticipant() : void
    {
        if (!is_null($_POST['participants'])) {
            $this->db_connector->deleteParticipantRows("(" . implode(",", $_POST['participants']) . ")");
        }
    }

    /**
     * @param $user_id
     * @return void
     */
    protected function saveUser($user_id) : void
    {
        $values = [
            ['integer', $this->protocol_id],
            ['integer', $user_id]
        ];
        $user = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        if (!is_int(array_search($user_id, array_column($user, "usr_id")))) {
            $this->db_connector->insertParticipant($values);
        }
    }
}
