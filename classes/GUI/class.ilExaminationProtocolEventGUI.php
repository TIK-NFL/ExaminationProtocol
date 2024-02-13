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
 * @ilCtrl_isCalledBy ilExaminationProtocolEventGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventInput
 * @ilCtrl_Calls ilExaminationProtocolEventGUI:  ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventInput
 */
class ilExaminationProtocolEventGUI extends ilExaminationProtocolTableBaseController
{
    /** @var bool  */
    private $configured;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::PROTOCOL_TAB_ID);
        $this->configured = true;
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]['assigned']);
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]['running_session']);
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]);
        unset($_SESSION['examination_protocol']['entry_id']);
    }

    protected function buildGUI(): void
    {
        $this->buildNotification();
        $this->buildToolbar();
        $this->table = new ilExaminationProtocolEventTableGUI($this, self::CMD_SHOW);
        $this->loadDataIntoTable();
        $this->tpl->setContent($this->table->getHTML());
        $this->tpl->printToStdout();
    }

    protected function buildToolbar(): void
    {
        if ($this->configured) {
            $btn = ilLinkButton::getInstance();
            $btn->setCaption($this->plugin->txt('event_table_btn_add_event'), false);
            $btn->setUrl($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class, self::CMD_SHOW));
            $this->toolbar->addButtonInstance($btn);
        }
        if ($this->protocol_has_entries) {
            $btn = ilLinkButton::getInstance();
            $btn->setCaption($this->plugin->txt('event_table_btn_delete_all_events'), false);
            $btn->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_CONFIRMATION));
            $this->toolbar->addButtonInstance($btn);
        }
    }

    private function loadDataIntoTable(): void
    {
        $event_entries = $this->db_connector->getAllProtocolEntriesByProtocolID($this->protocol_id);
        $data = [];
        foreach ($event_entries as $entry) {
            $entry['start'] = $this->utctolocal($entry['start']);
            $entry['end'] = $this->utctolocal($entry['end']);
            $entry['creation'] = $this->utctolocal($entry['creation']);
            $entry['last_edit'] = $this->utctolocal($entry['last_edit']);
            $participants = $this->db_connector->getAllProtocolParticipants($entry['entry_id']);
            if (!isset($entry['student_id'])) {
                $entry['student_id'] = '';
                $entry['student_names'] = '';
            }
            foreach ($participants as $participant) {
                $usr_id = $this->db_connector->getUserIDbyParticipantID($participant['participant_id']);
                if (isset($usr_id[0]['usr_id'])) {
                    $il_user_id = $usr_id[0]['usr_id'];
                    $matriculation = $this->db_connector->getMatriculationByUserID($il_user_id)[0]['matriculation'];
                    if ($matriculation == '') {
                        $matriculation = '--';
                    }
                    $res = $this->db_connector->getUsernameByUserID($il_user_id)[0];
                    $entry['student_id'] .= $res['lastname'] . ", " . $res['firstname'] . "(".$matriculation .", [".$res['login']."])</br>" ;
                }
            }
            $entry['event_type'] = $this->event_options[$entry['event']];
            $location = $this->db_connector->getLocationsByLocationID($entry['location_id']);
            if ($this->settings['location'] == '0' && isset($location[0]['location'])) {
                $entry['location'] = $location[0]['location'];
            } else {
                $entry['location'] = $this->plugin->txt('entry_dropdown_location_no_location');;
            }
            $supervisor = $this->db_connector->getSupervisorBySupervisorID($entry['supervisor_id']);
            if ($this->settings['supervision'] != '2' && isset($supervisor[0]['name'])) {
                $entry['supervisor'] = $supervisor[0]['name'];
            } else {
                $entry['supervisor'] = $this->plugin->txt('entry_dropdown_supervisor_no_supervisor');
            }
            $entry['last_edited_by'] = $this->db_connector->getLoginByUserID($entry['last_edited_by'])[0]['login'];
            $entry['created_by'] = $this->db_connector->getLoginByUserID($entry['created_by'])[0]['login'];

            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, 'entry_id', $entry['entry_id']);
            $edit_event_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class, self::CMD_SHOW);
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventParticipantsGUI::class, 'entry_id', $entry['entry_id']);
            $edit_participants_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventParticipantsGUI::class, self::CMD_SHOW);
            $this->ctrl->setParameterByClass(self::class, 'entry_id', $entry['entry_id']);
            $delete_event_url = $this->ctrl->getLinkTargetByClass(self::class, self::CMD_CONFIRMATION);
            $dd_items = [
                $this->ui_factory->button()->shy($this->plugin->txt('event_table_action_edit_event'), $edit_event_url),
                $this->ui_factory->button()->shy($this->plugin->txt('event_table_action_edit_participant'), $edit_participants_url),
                $this->ui_factory->button()->shy($this->plugin->txt('delete'), $delete_event_url)
            ];
            $dd_action = $this->ui_factory->dropdown()->standard($dd_items)
                ->withLabel($this->plugin->txt('event_table_action'));
            $entry['action'] = $this->renderer->render($dd_action);
            $data[] = $entry;
        }
        $this->table->setData($data);
    }

    private function buildNotification(): void
    {
        $info_message = "";
        $supervisors = $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id);
        if ($this->settings['supervision'] != '2' && empty($supervisors)) {
            $info_message .= $this->plugin->txt("event_table_info_supervisors");
        }
        $location = $this->db_connector->getAllLocationsByProtocolID($this->protocol_id);
        if ($this->settings['location'] != '1' && empty($location)) {
            $info_message .= $this->plugin->txt("event_table_info_locations");
        }
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        if (empty($participants)) {
            $info_message .= $this->plugin->txt("event_table_info_participants");
        }
        if (!empty($info_message)) {
            $this->configured = false;
            $this->tpl->setOnScreenMessage('info', $info_message);
        }
    }

    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd()) {
            case self::CMD_CONFIRMATION:
                $this->getConfirmationDialog();
                break;
        }
    }

    protected function getConfirmationDialog(): void
    {
        require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
        $confirmation_gui = new ilConfirmationGUI();
        $confirmation_gui->setHeaderText($this->plugin->txt('event_table_action_confirmation_question'));
        $confirmation_gui->setFormAction($this->ctrl->getFormAction($this, self::CMD_SHOW));
        $confirmation_gui->setCancel($this->lng->txt('cancel'), self::CMD_SHOW);
        $confirmation_gui->setConfirm($this->lng->txt('confirm'), self::CMD_DELETE);
        if (!empty($_REQUEST['entry_id'])) {
            $confirmation_gui->addHiddenItem('entry_id', $_REQUEST['entry_id']);
        } else {
            $confirmation_gui->addHiddenItem('protocol_id', $this->protocol_id);
        }
        $this->tpl->setContent($confirmation_gui->getHTML());
        $this->tpl->printToStdout();
    }

    protected function deleteContent(): void
    {
        if (!empty($_POST['entry_id'])) {
            $this->db_connector->deleteProtocolEntry($_POST['entry_id']);
            $this->db_connector->deleteAllProtocolParticipantByEntryId($_POST['entry_id']);
        } elseif (!empty($_POST['protocol_id'])) {
            $this->db_connector->deleteAllProtocolEntries($_POST['protocol_id']);
            $this->db_connector->deleteAllProtocolParticipantByProtocolId($_POST['protocol_id']);
        }
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
    }

    protected function saveContent()
    {
    }

    protected function applyFilter()
    {
    }

    protected function resetFilter()
    {
    }
}
