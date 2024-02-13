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
 * @ilCtrl_isCalledBy ilExaminationProtocolParticipantsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolParticipantsGUI: ilPermissionGUI, ilInfoScreenGUI, ilRepositoryGUI, ilRepositorySearchGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolParticipantsGUI extends ilExaminationProtocolTableBaseController
{
     /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::PARTICIPANT_TAB_ID);
    }

    /**
     * @throws ilCtrlException
     */
    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd()) {
            case '':
            case 'doUserAutoComplete':
            case 'addUserFromAutoComplete':
            case 'performSearch':
            case 'listUsers':
            case 'showSearch';
            case self::CMD_ADD_PARTICIPANTS:
                $rep_search = new ilRepositorySearchGUI();
                $ref_id = $_REQUEST['ref_id'];
                $rep_search->addUserAccessFilterCallable(function ($user_id) use ($ref_id) {
                    return $GLOBALS['DIC']->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
                        'render',
                        'render',
                        $ref_id,
                        $user_id
                    );
                });
                $rep_search->setCallback($this, 'addUser');
                $rep_search->setTitle($this->plugin->txt('participant_selector_title'));
                $this->ctrl->setReturn($this, 'addUser');
                $this->ctrl->forwardCommand($rep_search);
                $rep_search->tpl->printToStdout();
                break;
            case 'showSearchSelected':
            case 'addUser':
                $this->addUser();
                $this->buildGUI();
                break;

        }
    }

    public function addUser(array $user_ids = array()): void
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

    protected function buildGUI(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->loadDataIntoTable();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    protected function buildToolbar(): void
    {
        ilRepositorySearchGUI::fillAutoCompleteToolbar(
            $this,
            $this->toolbar,
            array(
                'auto_complete_name' => $this->lng->txt('user'),
                'submit_name' => $this->lng->txt('add'),
                'add_from_container' => $this->test_object->getRefId()
            ),
            true
        );
        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->plugin->txt('participant_btn_add_participants_title'), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI'));
        $this->toolbar->addButtonInstance($btn);
    }

    protected function buildTable(): void
    {
        $this->table = new ilExaminationProtocolParticipantsTableGUI($this, "show", "");
        $this->table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->table->setResetCommand(self::CMD_RESET_FILTER);
    }

    protected function loadDataIntoTable(): void
    {
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        $usr_participant_mapping = array_reduce($participants, function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, array());
        $usr_ids = array_keys($usr_participant_mapping);
        $usr_login = unserialize($_SESSION['form_texa_participant']['login'] ?? "") ;
        $usr_name = unserialize($_SESSION['form_texa_participant']['name'] ?? "") ;
        $usr_mrt = unserialize($_SESSION['form_texa_participant']['matriculation'] ?? "");
        $data = $this->db_connector->getAllParticipantsByUserIDandFilter(
            "'" . implode("', '", $usr_ids) . "'",
            $usr_login,
            $usr_name,
            $usr_mrt
        );
        foreach ($data as $index => $entry) {
            $data[$index]['participant_id'] = $usr_participant_mapping[$entry['usr_id']];
        }
        $this->table->setData($data);
    }

    protected function applyFilter(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->table->writeFilterToSession();
        $this->table->resetOffset();
        $this->loadDataIntoTable();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    protected function resetFilter(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->table->resetOffset();
        $this->table->resetFilter();
        $_SESSION['form_texa_participant']['login'] = '';
        $_SESSION['form_texa_participant']['name'] = '';
        $_SESSION['form_texa_participant']['matriculation'] = '';
        $this->loadDataIntoTable();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    protected function deleteContent(): void
    {
        if (!is_null($_POST['participants'])) {
            $this->db_connector->deleteParticipantRows("(" . implode(",", $_POST['participants']) . ")");
        }
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function saveUser($user_id): void
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

    protected function saveContent()
    {
    }
}
