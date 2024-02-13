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
use ILIAS\UI\Component\Panel\Listing\Standard;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolEventParticipantsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolEventParticipantsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolEventParticipantsGUI extends ilExaminationProtocolTableBaseController
{
    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::PROTOCOL_PARTICIPANT_TAB_ID);
        if (!empty($_REQUEST['entry_id']) && empty($_SESSION['examination_protocol']['entry_id'])) {
            $_SESSION['examination_protocol']['entry_id'] = $_REQUEST['entry_id'];
        }
    }

    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd()) {
            case 'assign':
                $this->assign();
                break;
            case 'unassign':
                $this->unassign();
                break;
        }
    }

    private function buildToolbar(): void
    {
        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->lng->txt("cancel"), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
        $this->toolbar->addButtonInstance($btn);

        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->plugin->txt("entry_button_protocol"), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SAVE));
        $this->toolbar->addButtonInstance($btn);
    }

    protected function buildNotification(): void
    {
        if ($_REQUEST['notification'] == 'assigned') {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('not_saved'));
        } elseif ($_REQUEST['notification'] == 'failure') {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('not_participants'));
        }
    }

    protected function buildGUI(): void
    {
        $this->buildToolbar();
        $this->buildNotification();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->loadDataIntoTable();
        $tableContent = $this->ui_factory->legacy($this->table->getHTML());
        $page = [$listing, $tableContent];
        $html = $this->renderer->render($page);
        $this->tpl->setContent($html);
        $this->tpl->printToStdout();
    }

    /**
     * @throws Exception
     */
    private function buildListing(): Standard
    {
        $event = $this->db_connector->getAllProtocolEntries($_SESSION['examination_protocol']['entry_id'])[0];
        $properties = [
            $this->plugin->txt("entry_type") => $this->event_options[$event['event']],
            $this->plugin->txt("description") => $event['comment'],
            $this->plugin->txt("entry_last_update") => $this->utctolocal($event['last_edit'] ?? ''),
        ];
        if ($this->settings['supervision'] != '2') {
            $supervisor = $this->db_connector->getSupervisorBySupervisorID($event['supervisor_id']);
            $supervisor = $supervisor[0]['name'] ?? '';
            $properties[$this->plugin->txt("entry_supervisor")] = $supervisor;
        }
        if ($this->settings['location'] == '0') {
            $db_result = $this->db_connector->getLocationsByLocationID($event['location_id']);
            if (isset($db_result[0]) ) {
                $location = $db_result[0]['location'];
            } else {
                $location = '';
            }
            $properties[$this->plugin->txt("entry_location")] = $location;
        }
        $list_item = $this->ui_factory->item()->standard($this->event_options[$event['event']])
            ->withDescription($event['comment'])
            ->withProperties($properties);
        return $this->ui_factory->panel()->listing()->standard("Examination Event", [
            $this->ui_factory->item()->group("", [$list_item])]);
    }

    private function buildTable(): void
    {
        $this->table = new ilExaminationProtocolEventParticipantsTableGUI($this, self::CMD_SHOW);
        $this->table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->table->setResetCommand(self::CMD_RESET_FILTER);
    }

    protected function saveContent(): void
    {
        $assigned_participant = $this->db_connector->getAllProtocolParticipants($_SESSION['examination_protocol']['entry_id']);
        $assigned_participant_ids = $this->db_connector->getAllProtocolParticipantIDs($_SESSION['examination_protocol']['entry_id']);
        $intersect_ids = array_intersect($assigned_participant_ids, $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned']);
        $add_ids = array_diff($_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'], $intersect_ids);
        $del_ids = array_diff($assigned_participant_ids, $intersect_ids);
        foreach ($assigned_participant as $participant) {
            if (in_array($participant['participant_id'], $del_ids)) {
                $this->db_connector->deleteProtocolParticipant($participant['propar_id']);
            }
        }
        foreach ($add_ids as $participant_id) {
            $values = [
                    ['integer', $this->protocol_id],
                    ['integer', $_SESSION['examination_protocol']['entry_id']],
                    ['integer', $participant_id],
                ];
            $this->db_connector->insertProtocolParticipant($values);
        }
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]['assigned']);
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]['running_session']);
        unset($_SESSION['examination_protocol'][$this->test_object->test_id]);
        unset($_SESSION['examination_protocol']['entry_id']);
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
    }

    private function assign(): void
    {
        if (isset($_POST['participants'])){
            foreach ($_POST['participants'] as $participant_id) {
                if (!in_array($participant_id, $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'])) {
                    $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'][] = $participant_id;
                }
            }
            $this->ctrl->setParameterByClass(self::class, 'notification', 'assigned');
        } else {
            $this->ctrl->setParameterByClass(self::class, 'notification', 'failure');
        }
        $this->ctrl->setParameterByClass(self::class, "entry_id", $_REQUEST['entry_id']);
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    private function unassign(): void
    {
        if (isset($_POST['participants'])) {
            foreach ($_POST['participants'] as $participant_id) {
                if (in_array($participant_id,
                    $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'])) {
                    $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'] = array_diff($_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'],
                        [$participant_id]);
                }
            }
            $this->ctrl->setParameterByClass(self::class, 'notification', 'assigned');
        } else {
            $this->ctrl->setParameterByClass(self::class, 'notification', 'failure');
        }
        $this->ctrl->setParameterByClass(self::class, "entry_id", $_REQUEST['entry_id']);
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function applyFilter(): void
    {
        $this->buildToolbar();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->table->writeFilterToSession();
        $this->table->resetOffset();
        $this->loadDataIntoTable();
        $table = $this->ui_factory->legacy($this->table->getHTML());
        $page = [$listing, $table];
        $html = $this->renderer->render($page);
        $this->tpl->setContent($html);
        $this->tpl->printToStdout();
    }

    protected function resetFilter(): void
    {
        $this->buildToolbar();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->table->resetOffset();
        $this->table->resetFilter();
        $_SESSION['form_texa_protocol_participant']['login'] = '';
        $_SESSION['form_texa_protocol_participant']['name'] = '';
        $_SESSION['form_texa_protocol_participant']['matriculation'] = '';
        $this->loadDataIntoTable();
        $table = $this->ui_factory->legacy($this->table->getHTML());
        $page = [$listing, $table];
        $html = $this->renderer->render($page);
        $this->tpl->setContent($html);
        $this->tpl->printToStdout();
    }

    private function loadDataIntoTable(): void
    {
        $usr_participant_mapping = array_reduce($this->db_connector->getAllParticipantsByProtocolID($this->protocol_id), function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, []);
        $usr_ids = array_keys($usr_participant_mapping);
        if (empty($_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'])
            && (empty($_SESSION['examination_protocol'][$this->test_object->test_id]['running_session'])
                || !$_SESSION['examination_protocol'][$this->test_object->test_id]['running_session']))
        {
            $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'] = $this->db_connector->getAllProtocolParticipantIDs($_SESSION['examination_protocol']['entry_id']);
        }
        $usr_login = unserialize($_SESSION['form_texa_protocol_participant']['login'] ?? "");
        $usr_name = unserialize($_SESSION['form_texa_protocol_participant']['name'] ?? "");
        $usr_mrt = unserialize($_SESSION['form_texa_protocol_participant']['matriculation'] ?? "");
        $data = $this->db_connector->getAllParticipantsByUserIDandFilter(
            "'" . implode("', '", $usr_ids) . "'",
            $usr_login,
            $usr_name,
            $usr_mrt
        );
        foreach ($data as $index => $entry) {
            $data[$index]['participant_id'] = $usr_participant_mapping[$entry['usr_id']];
            if (in_array($data[$index]['participant_id'], $_SESSION['examination_protocol'][$this->test_object->test_id]['assigned'])) {
                $data[$index]['glyph'] = $this->renderer->render($this->ui_factory->symbol()->glyph()->apply());
            } else {
                $data[$index]['glyph']  = $this->renderer->render($this->ui_factory->symbol()->glyph()->close());
            }
        }
        $this->table->setData($data);
    }

    protected function deleteContent()
    {
    }
}
