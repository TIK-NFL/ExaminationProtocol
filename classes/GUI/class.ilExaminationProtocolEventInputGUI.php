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
use ILIAS\UI\Component\Input\Field\Select;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolEventInputGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventTableGUI
 * @ilCtrl_Calls ilExaminationProtocolEventInputGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventTableGUI
 */
class ilExaminationProtocolEventInputGUI extends ilExaminationProtocolBaseController
{
    private ?array $entry;

    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::PROTOCOL_INPUT_TAB_ID);
        if (isset($_REQUEST['entry_id'])){
            $this->entry = $this->db_connector->getAllProtocolEntries($_REQUEST['entry_id'])[0];
        }
    }

    /**
     * @throws ilCtrlException
     */
    protected function buildGUI(): void
    {
        $this->buildNotifications();
        $this->buildToolbar();
        $this->buildFormContent();
        $this->tpl->printToStdout();
    }

    private function buildNotifications()
    {
        if (!empty($_REQUEST['info']) && $_REQUEST['info'] == 'empty_date') {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('entry_datetime_empty'));
        } elseif (!empty($_REQUEST['info']) && $_REQUEST['info'] == 'wrong_date') {
            $this->tpl->setOnScreenMessage('failure', $this->plugin->txt('entry_datetime_wrong'));
        }
    }

    /**
     * @throws ilCtrlException
     */
    private function buildToolbar(): void
    {
        $btn = $this->ui_factory->button()->standard($this->lng->txt('cancel'),
            $this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
        $this->toolbar->addComponent($btn);
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    protected function buildFormContent(): void
    {
        $event_inputs = $this->buildEventInput();
        if ($this->plugin_settings['supervision'] != '2') {
            $event_inputs[] = $this->buildSupervision();
        }

        if ($this->plugin_settings['location'] == '0') {
            $event_inputs[] = $this->buildLocation();
        }

        $section_input = $this->field_factory->section($event_inputs, $this->plugin->txt('entry_event_section'));
        $form_action = $this->ctrl->getFormAction($this, self::CMD_SAVE);
        $form = $this->ui_factory->input()->container()->form()->standard($form_action, [$section_input]);

        if ($this->request->getMethod() == 'POST') {
            $form = $form->withRequest($this->request);
        }
        $html = $this->renderer->render($form);
        // So the kitchensink sets the default button text of the button to "save" in the renderer ILIAS 7 und 8
        // in ILIAS/src/UI/Implementation/Component/Input/Container/Form/Renderer.php
        // we need a "next" TODO remove HTML edit, when KS has an edible button
        if (empty($_REQUEST['entry_id'])) {
            $html = str_replace(
                '<div class="il-standard-form-cmd"><button class="btn btn-default"   data-action="">Save</button>',
                '<div class="il-standard-form-cmd"><button class="btn btn-default"   data-action="">' . $this->plugin->txt("next") . '</button>',
                $html
            );
        }
        $this->tpl->setContent($html);
    }

    /**
     * @throws ilCtrlException
     */
    private function buildEventInput(): array
    {
        $data_factory = new ILIAS\Data\Factory();
        // load existing entry
        $start = $end = date('d.m.Y H:i');
        if (!empty($this->entry)) {
            $start = $this->utctolocal($this->entry['start']);
            $end = $this->utctolocal($this->entry['end']);
            $this->ctrl->setParameterByClass(self::class, 'entry_id', $_REQUEST['entry_id']);
        }
        $dt_start = $this->field_factory->dateTime($this->plugin->txt('entry_datetime_start_title'))
                                        ->withUseTime(true)
                                        ->withFormat($data_factory->dateFormat()->germanShort())
                                        ->withValue($start)
                                        ->withRequired(true);
        $dt_end = $this->field_factory->dateTime($this->plugin->txt('entry_datetime_end_title'))
                                      ->withUseTime(true)
                                      ->withFormat($data_factory->dateFormat()->germanShort())
                                      ->withValue($end)
                                      ->withRequired(true);
        $se_event_type = $this->field_factory->select($this->plugin->txt('entry_dropdown_event_title'), $this->event_options)
                                             ->withValue($this->entry['event'] ?? 0)
                                             ->withRequired(true);
        $ta_description = $this->field_factory->textarea($this->plugin->txt('description'))
                                              ->withValue($this->entry['comment'] ?? '');
        return [
            $dt_start,
            $dt_end,
            $se_event_type,
            $ta_description,
        ];
    }

    private function buildSupervision(): Select
    {
        $supervisors = $this->db_connector->getAllSupervisorsByProtocolID($this->protocol_id);
        $supervisor_options = array_column($supervisors, 'name', 'supervisor_id');
        $supervisor_options[0] = $this->plugin->txt('entry_dropdown_supervisor_no_supervisor');
        return $this->field_factory->select($this->plugin->txt('entry_dropdown_supervisor_title'), $supervisor_options)
                                   ->withValue($this->entry['supervisor_id'] ?? 0)
                                   ->withRequired(true);
    }

    private function buildLocation(): Select{
        $locations = $this->db_connector->getAllLocationsByProtocolID($this->protocol_id);
        $location_options = array_column($locations, 'location', 'location_id');
        $location_options[0] = $this->plugin->txt('entry_dropdown_location_no_location');
        $se_location = $this->field_factory->select($this->plugin->txt('entry_dropdown_location_title'), $location_options)
                                           ->withValue($this->entry['location_id'] ?? 0)
                                           ->withRequired(true);
        return $se_location;
    }

    /**
     * @throws JsonException
     * @throws ilCtrlException
     */
    public function saveContent(): void
    {
        if (empty($_POST['form_input_2']) || empty($_POST['form_input_3'])) {
            $this->ctrl->setParameterByClass(self::class, 'entry_id', $_REQUEST['entry_id']);
            $this->ctrl->setParameterByClass(self::class, 'info', 'empty_date');
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
        }
        if ($_POST['form_input_3'] < $_POST['form_input_2']) {
            $this->ctrl->setParameterByClass(self::class, 'entry_id', $_REQUEST['entry_id']);
            $this->ctrl->setParameterByClass(self::class, 'info', 'wrong_date');
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
        }

        global $ilUser;
        $supervisor = '';
        $location = '';
        // TODO get clear input names
        if ($this->plugin_settings['supervision'] != '2') {
            $supervisor = $_POST['form_input_6'];
        }
        if ($this->plugin_settings['location'] == '0') {
            if ($this->plugin_settings['supervision'] != '2') {
                $location = $_POST['form_input_7'];
            } else {
                $location = $_POST['form_input_6'];
            }
        }
        $date_now = new DateTime('now');
        $dt_now = gmdate('Y-m-d H:i:s', strtotime($date_now->format('Y-m-d H:i:s')));
        $start = gmdate('Y-m-d H:i:s', strtotime($_POST['form_input_2']));
        $end = gmdate('Y-m-d H:i:s', strtotime($_POST['form_input_3']));
        $user = $ilUser->getId();
        $values = [
            ['integer', $this->protocol_id],
            ['integer', $supervisor],
            ['integer', $location],
            ['date', $start],
            ['date', $end],
            ['date', $this->entry['creation'] ?? $dt_now],
            ['integer',$_POST['form_input_4']],
            ['text', $_POST['form_input_5']],
            ['date', $dt_now],
            ['integer', $user],
            ['integer', $this->entry['created_by'] ?? $user],
        ];
        if (!empty($_REQUEST['entry_id'])) {
            $where = [
                $this->db_connector::PROTOCOL_PRIMARY_KEY => ['integer', $this->entry['entry_id']]
            ];
            $this->db_connector->updateProtocolEntry($values, $where);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventGUI::class, self::CMD_SHOW));
        } else {
            $entry_id = $this->db_connector->insertProtocolEntry($values);
            $this->ctrl->setParameterByClass(ilExaminationProtocolEventParticipantsGUI::class, 'entry_id', $entry_id);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(ilExaminationProtocolEventParticipantsGUI::class, self::CMD_SHOW));
        }
    }

    protected function deleteContent()
    {
    }
}
