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
 * @ilCtrl_isCalledBy ilExaminationProtocolParticipantsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolParticipantsGUI: ilPermissionGUI, ilInfoScreenGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolParticipantsGUI extends ilExaminationProtocolBaseController
{
    /** @var ilExaminationProtocolParticipantsTableGUI */
    private $participant_table;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(ilExaminationProtocolBaseController::PARTICIPANT_TAB_ID);
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            case self::CMD_DELETE:
                $this->delete();
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
                $rep_search->setTitle($this->plugin->txt('examination_protocol_participant_selector_title'));
                $this->ctrl->setReturn($this, 'show');
                $this->ctrl->forwardCommand($rep_search);
                break;
            case self::CMD_APPLY_FILTER:
                $this->applyFilter();
                break;
            case self::CMD_RESET_FILTER:
                $this->resetFilter();
                break;
            case 'addUser':
                $this->addUser();
                $this->show();
                break;
            case self::CMD_SHOW:
            default:
                $this->show();
                break;
        }
    }

    /**
     * @param array|null $user_ids
     * @return void
     */
    public function addUser(array $user_ids = array()) : void
    {
        if (empty($user_ids) && !empty($_POST['user'])) {
            $user_ids = $_POST['user'];
        }
        foreach ($user_ids as $user_id) {
            $this->saveUser($user_id);
        }
    }

    /**
     * @return void
     */
    protected function show() : void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->loadData();
        $this->tpl->setContent($this->participant_table->getHTML());
    }

    /**
     * @return void
     */
    protected function buildToolbar() : void
    {
        if (!$this->protocol_has_entries) {
            // toolbar // no Kitchensink alternative jet
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

            // search button with special name
            $btn = ilLinkButton::getInstance();
            $btn->setCaption($this->plugin->txt('examination_protocol_participant_btn_add_participants_title'), false);
            $btn->setUrl($this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI'));
            $this->toolbar->addButtonInstance($btn);
        } else {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt("lock"));
        }
    }

    /**
     * @return void
     */
    protected function buildTable() : void
    {
        // table
        $this->participant_table = new ilExaminationProtocolParticipantsTableGUI($this, "show", "", $this->protocol_has_entries);
        // filter
        $this->participant_table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->participant_table->setResetCommand(self::CMD_RESET_FILTER);
    }

    /**
     * @return void
     */
    protected function loadData() : void
    {
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        $usr_participant_mapping = array_reduce($participants, function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, array());
        $usr_ids = array_keys($usr_participant_mapping);
        // so when reseting the table filter the $_SESSION variables are transformed into boolen (false) since for some reason I had to implement the filter myself?
        $usr_login = $_SESSION['form_texa_participant']['login'] ?? "";
        $usr_name = $_SESSION['form_texa_participant']['name'] ?? "";
        $usr_mrt = $_SESSION['form_texa_participant']['mrt'] ?? "";
        if ($usr_mrt === false) {
            $usr_login = $usr_name = $usr_mrt = "";
        } else {
            $usr_login = unserialize($usr_login);
            $usr_name = unserialize($usr_name);
            $usr_mrt = unserialize($usr_mrt);
        }
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
     * @return void
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
     * @return void
     */
    private function resetFilter() : void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->participant_table->resetOffset();
        $this->participant_table->resetFilter();
        $this->loadData();
        $this->tpl->setContent($this->participant_table->getHTML());
    }

    /**
     * @return string
     */
    public function getHTML() : string
    {
        return "";
    }

    /**
     * @return void
     */
    protected function delete() : void
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
        // build input Array
        $values = [
            ['integer', $this->protocol_id],
            ['integer', $user_id]
        ];
        // update Database
        $user = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        if (!is_int(array_search($user_id, array_column($user, "usr_id")))) {
            $this->db_connector->insertParticipant($values);
        }
    }
}
