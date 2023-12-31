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
use ILIAS\UI\Component\Panel\Listing\Standard;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @version  $Id$
 * @ilCtrl_isCalledBy ilExaminationProtocolEventParticipantsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolEventParticipantsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolEventParticipantsGUI extends ilExaminationProtocolBaseController
{
    /** @var ilExaminationProtocolEventParticipantsTableGUI */
    private $participant_table;
    /** @var string */
    protected $html;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        if (!empty($_REQUEST['entry_id']) && empty($_SESSION['examination_protocol']['entry_id'])) {
            $_SESSION['examination_protocol']['entry_id'] = $_REQUEST['entry_id'];
        }
    }

    /**
     * @return void
     */
    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            case self::CMD_SHOW:
                $this->buildParticipantForm();
                break;
            case self::CMD_APPLY_FILTER:
                $this->applyFilter();
                break;
            case self::CMD_RESET_FILTER:
                $this->resetFilter();
                break;
            case self::CMD_SAVE:
                $this->save();
                break;
            case 'assign':
                $this->assign();
                $this->buildParticipantForm();
                break;
            case 'unassign':
                $this->unassign();
                $this->buildParticipantForm();
                break;
        }
    }

    /**
     * @return string
     */
    public function getHTML() : string
    {
        return $this->html;
    }

    /**
     * @return void
     */
    private function buildToolbar() : void
    {
        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->plugin->txt("entry_button_protocol"), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SAVE));
        $this->toolbar->addButtonInstance($btn);
    }

    /**
     * @return void
     */
    private function buildParticipantForm() : void
    {
        $this->buildToolbar();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->loadDataIntoTable();
        $table = $this->ui_factory->legacy($this->participant_table->getHTML());
        $page = [$listing, $table];
        $this->html = $this->renderer->render($page);
    }

    /**
     * @return Standard
     */
    private function buildListing() : Standard
    {
        $event = $this->db_connector->getAllProtocolEntries($_SESSION['examination_protocol']['entry_id'])[0];
        $properties = [
            $this->plugin->txt("entry_type") => $this->event_options[$event['event']],
            $this->plugin->txt("description") => $event['comment'],
            $this->plugin->txt("entry_last_update") => date("H:m d.m.y", strtotime($event['last_edit'])),
        ];
        if ($this->settings['supervision'] != '2') {
            $supervisor = $this->db_connector->getSupervisorBySupervisorID($event['supervisor_id'])[0]['name'];
            $properties[$this->plugin->txt("entry_supervisor")] = $supervisor;
        }
        if ($this->settings['location'] == '0') {
            $location = $this->db_connector->getLocationsByLocationID($event['location_id'])[0]['location'];
            $properties[$this->plugin->txt("entry_location")] = $location;
        }
        $list_item = $this->ui_factory->item()->standard($this->event_options[$event['event']])
            ->withDescription($event['comment'])
            ->withProperties($properties);
        $listing = $this->ui_factory->panel()->listing()->standard("Examination Event", [
            $this->ui_factory->item()->group("", [$list_item])]);
        return $listing;
    }

    /**
     * @return void
     */
    private function buildTable() : void
    {
        // table
        $this->participant_table = new ilExaminationProtocolEventParticipantsTableGUI($this, "show");
        // filter
        $this->participant_table->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->participant_table->setResetCommand(self::CMD_RESET_FILTER);
    }

    /**
     * @return void
     */
    private function save() : void
    {
        $assigned_participant = $this->db_connector->getAllProtocolParticipants($_SESSION['examination_protocol']['entry_id']);
        $assigned_participant_ids = $this->db_connector->getAllProtocolParticipantIDs($_SESSION['examination_protocol']['entry_id']);
        $intersect_ids = array_intersect($assigned_participant_ids, $_SESSION['examination_protocol']['assigned']);
        $add_ids = array_diff($_SESSION['examination_protocol']['assigned'], $intersect_ids);
        $del_ids = array_diff($assigned_participant_ids, $intersect_ids);

        // delete assignes
        foreach ($assigned_participant as $participant) {
            if (in_array($participant['participant_id'], $del_ids)) {
                $this->db_connector->deleteProtocolParticipant($participant['propar_id']);
            }
        }

        // add new assignes
        foreach ($add_ids as $participant_id) {
            $values = [
                    ['integer', $this->protocol_id],
                    ['integer', $_SESSION['examination_protocol']['entry_id']],
                    ['integer', $participant_id],
                ];
            $this->db_connector->insertProtocolParticipant($values);
        }
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
    }

    /**
     * @return void
     */
    private function assign() : void
    {
        $this->tpl->setOnScreenMessage('info', $this->plugin->txt('examination_protocol_not_saved'));
        foreach ($_POST['participants'] as $participant_id) {
            if (!in_array($participant_id, $_SESSION['examination_protocol']['assigned'])) {
                $_SESSION['examination_protocol']['assigned'][] = $participant_id;
            }
        }
    }

    /**
     * @return void
     */
    private function unassign() : void
    {
        $this->tpl->setOnScreenMessage('info', $this->plugin->txt('examination_protocol_not_saved'));
        foreach ($_POST['participants'] as $participant_id) {
            if (in_array($participant_id, $_SESSION['examination_protocol']['assigned'])) {
                $_SESSION['examination_protocol']['assigned'] = array_diff($_SESSION['examination_protocol']['assigned'], [$participant_id]);
            }
        }
    }

    /**
     * @return void
     */
    private function applyFilter() : void
    {
        $this->buildToolbar();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->participant_table->writeFilterToSession();
        $this->participant_table->resetOffset();
        $this->loadDataIntoTable();
        $table = $this->ui_factory->legacy($this->participant_table->getHTML());
        $page = [$listing, $table];
        $this->html = $this->renderer->render($page);
    }

    /**
     * @return void
     */
    private function resetFilter() : void
    {
        $this->buildToolbar();
        $listing = $this->buildListing();
        $this->buildTable();
        $this->participant_table->resetOffset();
        $this->participant_table->resetFilter();
        $this->loadDataIntoTable();
        $table = $this->ui_factory->legacy($this->participant_table->getHTML());
        $page = [$listing, $table];
        $this->html = $this->renderer->render($page);
    }

    /**
     * @return void
     */
    private function loadDataIntoTable() : void
    {
        $usr_participant_mapping = array_reduce($this->db_connector->getAllParticipantsByProtocolID($this->protocol_id), function ($result, $item) {
            $result[$item['usr_id']] = $item['participant_id'];
            return $result;
        }, []);
        $usr_ids = array_keys($usr_participant_mapping);
        if (empty($_SESSION['examination_protocol']['assigned'])) {
            $_SESSION['examination_protocol']['assigned'] = $this->db_connector->getAllProtocolParticipantIDs($_SESSION['examination_protocol']['entry_id']);
        }
        $usr_login = $_SESSION['form_texa_protocol_participant']['login'] ?? "";
        $usr_name = $_SESSION['form_texa_protocol_participant']['name'] ?? "";
        $usr_mrt = $_SESSION['form_texa_protocol_participant']['mrt'] ?? "";
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
            if (in_array($data[$index]['participant_id'], $_SESSION['examination_protocol']['assigned'])) {
                $data[$index]['assigned'] = true;
            } else {
                $data[$index]['assigned'] = false;
            }
        }
        $this->participant_table->setData($data);
    }
}
