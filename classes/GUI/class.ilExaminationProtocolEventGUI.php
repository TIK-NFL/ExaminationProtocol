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
 * @author ulf Kunze <ulf.kunze@tik.uni-stuttgart.de>
 * @version  $Id$
 * @ilCtrl_isCalledBy ilExaminationProtocolEventGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventInput
 * @ilCtrl_Calls ilExaminationProtocolEventGUI:  ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventInput
 */
class ilExaminationProtocolEventGUI extends ilExaminationProtocolBaseController
{
    /** @var ilExaminationProtocolEventTableGUI */
    private $protocolTable;
    /** @var bool  */
    private $configured;

    public function __construct()
    {
        parent::__construct();
        $this->configured = true;
        // tab
        $this->tabs->activateSubTab(self::PROTOCOL_TAB_ID);
        // clear session entry...
        unset($_SESSION['examination_protocol']['assigned']);
        unset($_SESSION['examination_protocol']['entry_id']);
    }

    private function buildGUI() : void {
        // notification
        $this->buildInfo();
        // toolbar
        if ($this->configured) {
            $btn = ilLinkButton::getInstance();
            $btn->setCaption($this->plugin->txt("examination_protocol_event_table_btn_add_event"), false);
            $btn->setUrl($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class, self::CMD_SHOW));
            $this->toolbar->addButtonInstance($btn);
        }

        if ($this->protocol_has_entries){
            $btn = ilLinkButton::getInstance();
            $btn->setCaption($this->plugin->txt("examination_protocol_event_table_btn_delete_all_events"), false);
            $btn->setUrl($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_CONFIRMATION));
            $this->toolbar->addButtonInstance($btn);
        }
        $this->protocolTable = new ilExaminationProtocolEventTableGUI($this, "show");
        $this->loadData();
        $this->tpl->setContent($this->protocolTable->getHTML());
    }

    private function loadData() : void {
        $event_entries = $this->db_connector->getAllProtocolEntriesByProtocolID($this->protocol_id);
        $data = [];
        foreach ($event_entries as $entry) {
            $participants = $this->db_connector->getAllProtocolParticipants($entry['entry_id']);
            // participants
            foreach ($participants as $participant) {
                $il_user_id = $this->db_connector->getUserIDbyParticipantID($participant['participant_id'])[0]['usr_id'];
                $matriculation = $this->db_connector->getMatriculationByUserID($il_user_id)[0]['matriculation'];
                $res = $this->db_connector->getUsernameByUserID($il_user_id)[0];
                $entry['student_id'] .= $matriculation. " (" . $res['lastname'] . ", " . $res['firstname'] . ") </br>" ;
            }
            // event
            $entry['event'] = $this->event_options[$entry['event']];
            // location
            $entry['location'] = $this->db_connector->getLocationsByLocationID($entry['location_id'])[0]['location'];
            // supervisor
            $entry['supervisor'] = $this->db_connector->getSupervisorBySupervisorID($entry['supervisor_id'])[0]['name'];
            // write into selcted set
            // last edit user
            $entry['last_edited_by'] = $this->db_connector->getLoginByUserID($entry['last_edited_by'])[0]['login'];
            // creation user
            $entry['created_by'] = $this->db_connector->getLoginByUserID($entry['created_by'])[0]['login'];
            // Action

            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "entry_id", $entry['entry_id']);
            $edit_event_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class,self::CMD_SHOW);

            $this->ctrl->setParameterByClass(ilExaminationProtocolEventParticipantsGUI::class, "entry_id", $entry['entry_id']);
            $edit_participants_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventParticipantsGUI::class,self::CMD_SHOW);

            $this->ctrl->setParameterByClass(self::class, "entry_id", $entry['entry_id']);
            $delete_event_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_CONFIRMATION);
            $dd_items = [
                $this->ui_factory->button()->shy($this->plugin->txt("examination_protocol_event_table_action_edit_event"), $edit_event_url),
                $this->ui_factory->button()->shy($this->plugin->txt("examination_protocol_event_table_action_edit_participant"), $edit_participants_url),
                $this->ui_factory->button()->shy($this->plugin->txt("examination_protocol_delete"), $delete_event_url)
            ];
            $dd_action = $this->ui_factory->dropdown()->standard($dd_items)
                ->withLabel($this->plugin->txt("examination_protocol_event_table_action"));
            $entry['action'] = $this->renderer->render($dd_action);
            $data[] = $entry;
        }
        $this->protocolTable->setData($data);
    }

    private function buildInfo() : void {
        $info_message = "";
        // supervisors
        $supervisors = $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id);
        if ( $this->settings['supervision'] != '2' && empty($supervisors)) {
            $info_message .= $this->plugin->txt("examination_protocol_event_table_info_supervisors");
        }
        // locations
        $location = $this->db_connector->getAllLocationsByProtocolID($this->protocol_id);
        if ($this->settings['location'] != '1' && empty($location)){
            $info_message .= $this->plugin->txt("examination_protocol_event_table_info_locations");
        }
        // participants
        $participants = $this->db_connector->getAllParticipantsByProtocolID($this->protocol_id);
        if (empty($participants)){
            $info_message .= $this->plugin->txt("examination_protocol_event_table_info_participants");
        }
        if(!empty($info_message)){
            $this->configured = false;
            $this->tpl->setOnScreenMessage('info', $info_message);
        }
    }

    public function executeCommand(): void {
        switch ($this->ctrl->getCmd()){
            case self::CMD_SHOW:
                $this->buildGUI();
                break;
            case self::CMD_DELETE:
                $this->delete();
                $this->buildGUI();
                break;
            case self::CMD_CONFIRMATION:
                $this->getConfirmationDialog();
                break;
        }
    }

    protected function getConfirmationDialog() : void {
        require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
        $confirmation_gui = new ilConfirmationGUI();
        $confirmation_gui->setHeaderText($this->plugin->txt('examination_protocol_event_table_action_confirmation_question'));
        $confirmation_gui->setFormAction($this->ctrl->getFormAction($this, self::CMD_SHOW));
        $confirmation_gui->setCancel($this->lng->txt("cancel"), self::CMD_SHOW);
        $confirmation_gui->setConfirm($this->lng->txt("confirm"), self::CMD_DELETE);
        if (!empty($_REQUEST['entry_id'])) {
            $confirmation_gui->addHiddenItem('entry_id', $_REQUEST['entry_id']);
        } else {
            $confirmation_gui->addHiddenItem('protocol_id', $this->protocol_id);
        }
        $this->tpl->setContent($confirmation_gui->getHTML());
    }

    protected function delete() : void {
        if (!empty($_POST['entry_id'])){
            $this->db_connector->deleteProtocolEntry($_POST['entry_id']);
            $this->db_connector->deleteAllProtocolParticipantByEntryId($_POST['entry_id']);
        } else if (!empty($_POST['protocol_id'])){
            $this->db_connector->deleteAllProtocolEntries($_POST['protocol_id']);
            $this->db_connector->deleteAllProtocolParticipantByProtocolId($_POST['protocol_id']);
        }
    }

    public function getHTML() : string
    {
        return "";
    }

}
