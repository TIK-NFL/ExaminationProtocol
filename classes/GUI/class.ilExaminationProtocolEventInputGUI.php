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
 * @ilCtrl_isCalledBy ilExaminationProtocolEventInputGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventTableGUI
 * @ilCtrl_Calls ilExaminationProtocolEventInputGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventTableGUI
 */
class ilExaminationProtocolEventInputGUI extends ilExaminationProtocolBaseController
{
    /** @var mixed */
    private $form;
    /** @var DateTime  */
    private $date_now;
    private $html;
    private $entry;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        // date
        $this->date_now = new DateTime('now');
        $this->entry = $this->db_connector->getAllProtocolEntries($_REQUEST['entry_id'])[0];
    }

    private function buildEventForm() : void
    {
        // info
        if (!empty($_REQUEST['info']) && $_REQUEST['info'] == 'empty_date') {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('entry_datetime_empty'));
        } elseif (!empty($_REQUEST['info']) && $_REQUEST['info'] == 'wrong_date') {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('entry_datetime_wrong'));
        }
        $data_factory = new ILIAS\Data\Factory();
        // load existing entry
        $start = $end = null;
        if (!empty($this->entry)) {
            $start = date("d.m.Y H:i", strtotime($this->entry['start']));
            $end = date("d.m.y H:i", strtotime($this->entry['end']));
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "entry_id", $_REQUEST['entry_id']);
        }

        $this->buildToolbar();

        // event input
        $dt_start = $this->field_factory->dateTime($this->plugin->txt("entry_datetime_start_title"))
            ->withUseTime(true)
            ->withFormat($data_factory->dateFormat()->germanShort())
            ->withValue($start ?? $this->date_now->format("d.m.Y H:i"))
            ->withRequired(true);
        $dt_end = $this->field_factory->dateTime($this->plugin->txt("entry_datetime_end_title"))
            ->withUseTime(true)
            ->withFormat($data_factory->dateFormat()->germanShort())
            ->withValue($end ?? $this->date_now->format("d.m.Y H:i"))
            ->withRequired(true);
        $se_event_type = $this->field_factory->select($this->plugin->txt("entry_dropdown_event_title"), $this->event_options)
            ->withValue($this->entry['event'] ?? 0);
        $ta_description = $this->field_factory->textarea($this->plugin->txt("description"))
        ->withValue($this->entry['comment'] ?? "");
        $event_inputs = [
            $dt_start,
            $dt_end,
            $se_event_type,
            $ta_description,
        ];

        if ($this->settings['supervision'] != '2') {
            $supervisors = $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id);
            $supervisor_options = array_column($supervisors, 'name', 'supervisor_id');
            $supervisor_options[0] = $this->plugin->txt("entry_dropdown_supervisor_no_supervisor");

            $se_supervisor = $this->field_factory->select($this->plugin->txt("entry_dropdown_supervisor_title"), $supervisor_options)
                ->withValue($this->entry['supervisor_id'] ?? 0);
            $event_inputs[] = $se_supervisor;
        }

        if ($this->settings['location'] == '0') {
            $locations = $this->db_connector->getAllLocationsByProtocolID($this->protocol_id);
            $location_options = array_column($locations, 'location', 'location_id');
            $location_options[0] = $this->plugin->txt("entry_dropdown_location_no_location");

            $se_location = $this->field_factory->select($this->plugin->txt("entry_dropdown_location_title"), $location_options)
                ->withValue($this->entry['location_id'] ?? 0);
            $event_inputs[] = $se_location;
        }
        $section_input = $this->field_factory->section($event_inputs, $this->plugin->txt("entry_event_section"));

        // complete form
        $site = [
            $section_input,
        ];

        $form_action = $this->ctrl->getFormAction($this, self::CMD_SAVE);
        $this->form = $this->ui_factory->input()->container()->form()->standard($form_action, $site);

        if ($this->request->getMethod() == "POST") {
            $this->form = $this->form->withRequest($this->request);
        }
        $this->html = $this->renderer->render($this->form);
        // So the kitchensink sets the default button text of the button to "save" in the renderer ILIAS 7 und 8
        // $submit_button = $f->button()->standard($this->txt("save"), "");
        // in ILIAS/src/UI/Implementation/Component/Input/Container/Form/Renderer.php
        // we need a "next" TODO remove HTML edditing when KS has an edible button
        if (empty($_REQUEST['entry_id'])) {
            $this->html = str_replace(
                '<div class="il-standard-form-cmd"><button class="btn btn-default"   data-action="">Save</button>',
                '<div class="il-standard-form-cmd"><button class="btn btn-default"   data-action="">' . $this->plugin->txt("next") . '</button>',
                $this->html
            );
        }
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            case self::CMD_SAVE:
                $this->save();
                break;
            default:
            case self::CMD_SHOW:
                $this->buildEventForm();
                break;
        }
    }

    public function getHTML() : string
    {
        return $this->html;
    }

    private function buildToolbar() : void
    {
        $btn = ilLinkButton::getInstance();
        $btn->setCaption($this->lng->txt("cancel"), false);
        $btn->setUrl($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
        $this->toolbar->addButtonInstance($btn);
    }

    private function save() : void
    {
        if (empty($_POST['form_input_2']) || empty($_POST['form_input_3'])) {
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "entry_id", $_REQUEST['entry_id']);
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "info", 'empty_date');
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class, self::CMD_SHOW));
        }
        if ($_POST['form_input_3'] < $_POST['form_input_2']) {
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "entry_id", $_REQUEST['entry_id']);
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventInputGUI::class, "info", 'wrong_date');
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventInputGUI::class, self::CMD_SHOW));
        }

        global $ilUser;
        $supervisor = null;
        $location = null;
        // TODO get clear input names
        if ($this->settings['supervision'] != '2') {
            $supervisor = $_POST["form_input_6"];
        }
        if ($this->settings['location'] == '0') {
            if ($this->settings['supervision'] != '2') {
                $location = $_POST["form_input_7"];
            } else {
                $location = $_POST["form_input_6"];
            }
        }
        $dt_now = $this->date_now->format("Y-m-d H:i:s");
        $start = date("Y-m-d H:i:s", strtotime($_POST["form_input_2"]));
        $end = date("Y-m-d H:i:s", strtotime($_POST["form_input_3"]));
        $user = $ilUser->getId();
        $values = [
            // 0 protocol_id
            ['integer', $this->protocol_id],
            // 1 supervisor_id
            ['integer', $supervisor],
            // 2 location_id
            ['integer', $location],
            // 3 start
            ['date', $start],
            // 4 end
            ['date', $end],
            // 5 creation
            ['date', $this->entry['creation'] ?? $dt_now],
            // 6 event type
            ['integer',$_POST["form_input_4"]],
            // 7 comment
            ['text', $_POST["form_input_5"]],
            // 8 last_edit
            ['date', $dt_now],
            // 9 last_edit_by
            ['integer', $user],
            // 10 created_by
            ['integer', $this->entry['created_by'] ?? $user],
        ];
        if (!empty($_REQUEST['entry_id'])) {
            $where = [
                $this->db_connector::PROTOCOL_PRIMARY_KEY => ['integer', $this->entry['entry_id']]
            ];
            $this->db_connector->updateProtocolEntry($values, $where);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
        } else {
            // call Participant gui
            $entry_id = $this->db_connector->insertProtocolEntry($values);
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventParticipantsGUI::class, "entry_id", $entry_id);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventParticipantsGUI::class, self::CMD_SHOW));
        }
    }
}
