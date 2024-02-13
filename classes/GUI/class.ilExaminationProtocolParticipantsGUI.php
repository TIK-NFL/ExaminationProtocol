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
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::PARTICIPANT_TAB_ID);
    }

    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd()) {
            case '':
            case 'doUserAutoComplete':
            case 'addUserFromAutoComplete':
            case 'performSearch':
            case 'listUsers':
            case 'showSearch':
            case self::CMD_ADD_PARTICIPANTS:
                $rep_search = new ilRepositorySearchGUI();
                $ref_id = intval($_REQUEST['ref_id']);
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
                $this->tpl->printToStdout();
                break;
            case 'post':
                $tmp = 0;
                break;
            case 'addUser':
            case 'showSearchSelected':
                $this->addUser();
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
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
    }

    /**
     * @throws ilCtrlException
     * @throws ilException
     */
    protected function buildGUI(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->loadData();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    /**
     * @throws ilCtrlException
     */
    protected function buildToolbar(): void
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

    protected function buildTable(): void
    {
        $this->table = new ilExaminationProtocolParticipantsTableGUI($this, self::CMD_SHOW, '');
        $this->table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->table->setResetCommand(self::CMD_RESET_FILTER);
    }

    protected function loadData(): void
    {
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        $usr_participant_mapping = array_reduce($participants, function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, array());
        $usr_ids = array_keys($usr_participant_mapping);

        if (isset($_SESSION['form_texa_participant_login']) && $_SESSION['form_texa_participant_login'] != ''){
            $usr_login = unserialize($_SESSION['form_texa_participant_login']);
        } else {
            $usr_login = '';
        }
        if (isset($_SESSION['form_texa_participant_name']) && $_SESSION['form_texa_participant_name'] != ''){
            $usr_name = unserialize($_SESSION['form_texa_participant_name']);
        } else {
            $usr_name = '';
        }
        if (isset($_SESSION['form_texa_participant_matriculation']) && $_SESSION['form_texa_participant_matriculation'] != ''){
            $usr_mrt = unserialize($_SESSION['form_texa_participant_matriculation']);
        } else {
            $usr_mrt = '';
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
        $this->table->setData($data);
    }

    /**
     * @throws ilException
     * @throws ilCtrlException
     */
    protected function applyFilter(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->table->writeFilterToSession();
        $this->table->resetOffset();
        $this->loadData();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    /**
     * @throws ilException
     * @throws ilCtrlException
     */
    protected function resetFilter(): void
    {
        $this->buildToolbar();
        $this->buildTable();
        $this->table->resetOffset();
        $this->table->resetFilter();
        $_SESSION['form_texa_participant_login'] = '';
        $_SESSION['form_texa_participant_name'] = '';
        $_SESSION['form_texa_participant_matriculation'] = '';
        $this->loadData();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    protected function deleteContent(): void
    {
        if (!is_null($_POST['participants'])) {
            $this->db_connector->deleteParticipantRows("(" . implode(",", $_POST['participants']) . ")");
        }
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
    }

    protected function saveUser($user_id): void
    {
        $values = [
            ['integer', $this->protocol_id],
            ['integer', $user_id]
        ];
        $user = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        if (!is_int(array_search($user_id, array_column($user, 'usr_id')))) {
            $this->db_connector->insertParticipant($values);
        }
    }

    protected function saveContent()
    {
    }

}
