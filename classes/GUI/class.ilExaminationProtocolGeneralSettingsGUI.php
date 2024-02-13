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
use ILIAS\UI\Component\Input\Field\Section;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolGeneralSettingsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolGeneralSettingsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolGeneralSettingsGUI extends ilExaminationProtocolBaseController
{
    private $form;

    /**
     * @throws ilCtrlException
     */
    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::GENERAL_SETTINGS_TAB_ID);
        $this->buildForm();
    }

    public function buildGUI()
    {
        $this->buildNotification();
        $this->buildForm();
        $this->tpl->setContent($this->renderer->render($this->form));
        $this->tpl->printToStdout();
    }

    protected function buildForm(): void
    {
        $site = [
            $this->buildGeneralSettingsSection(),
            $this->buildExaminationTypeSection(),
            $this->buildSupervisionSection(),
            $this->buildMaterialSection(),
            $this->buildLocationSection()
        ];

        $form_action = $this->ctrl->getFormAction($this, self::CMD_SHOW);
        if (!$this->protocol_has_entries) {
            $form_action = $this->ctrl->getFormAction($this, self::CMD_SAVE);
        }

        $this->form = $this->ui_factory->input()->container()->form()->standard($form_action, $site);
        if ($this->request->getMethod() == 'POST') {
            $this->form = $this->form->withRequest($this->request);
        }
    }

    protected function buildNotification(): void
    {
        if ($this->protocol_has_entries) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('lock'));
        } elseif (isset($_REQUEST['Success']) && $_REQUEST['Success'] == 'true'){
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('saved_successfully'));
        }
    }

    private function buildGeneralSettingsSection(): Section
    {
        $text_title = $this->field_factory->text($this->plugin->txt('settings_general_settings_text_title'))
                                          ->withValue($this->plugin_settings['protocol_title'] ?? $this->test_object->getTitle())
                                          ->withRequired(true)
                                          ->withDisabled($this->protocol_has_entries);
        $ta_desc = $this->field_factory->textarea($this->lng->txt('desc'))
                                       ->withValue($this->plugin_settings['protocol_desc'] ?? '')
                                       ->withDisabled($this->protocol_has_entries);
        $sectionInputsGeneral = [
            $text_title,
            $ta_desc
        ];
        return $this->field_factory->section($sectionInputsGeneral, $this->plugin->txt('sub_tab_settings'));
    }

    private function buildExaminationTypeSection(): Section
    {
        $rb_type = $this->field_factory->radio($this->plugin->txt('settings_examination_type_radiobutton_title'))
                                       ->withOption('0', $this->plugin->txt('settings_examination_type_radiobutton_option_online'))
                                       ->withOption('1', $this->plugin->txt('settings_examination_type_radiobutton_option_upload'))
                                       ->withValue($this->plugin_settings['type_exam'] ?? '0')
                                       ->withDisabled($this->protocol_has_entries);
        $g_software_ilias = $this->field_factory->group(
            [],
            $this->plugin->txt('settings_examination_type_radiobutton_software_only_ilias_line')
        );
        $ta_software = $this->field_factory->textarea(
            $this->plugin->txt('settings_examination_type_textarea_additional_software_title')
        )
                                           ->withValue($this->plugin_settings['type_desc'] ?? '')
                                           ->withByline($this->plugin->txt('settings_examination_type_radiobutton_software_additional_software_byline'));
        $g_Software_add = $this->field_factory->group(
            [ 0 => $ta_software],
            $this->plugin->txt('settings_examination_type_radiobutton_software_additional_software_line')
        );
        $sg_software = $this->field_factory->switchableGroup(
            [$g_software_ilias, $g_Software_add],
            $this->plugin->txt('settings_examination_type_radiobutton_software_title')
        )
                                           ->withValue($this->plugin_settings['type_only_ilias'] ?? '0')
                                           ->withDisabled($this->protocol_has_entries);
        $section_inputs_examination = [
            $rb_type,
            $sg_software
        ];
        return $this->field_factory->section($section_inputs_examination, $this->plugin->txt('settings_examination_type_section_type_title'));
    }

    private function buildSupervisionSection(): Section
    {
        $rb_sup_onsite = $this->field_factory->radio($this->plugin->txt('settings_supervision_radiobutton_supervision_title'))
                                             ->withOption('0', $this->plugin->txt('settings_supervision_radiobutton_option_onsite'))
                                             ->withOption('1', $this->plugin->txt('settings_supervision_radiobutton_option_remote'))
                                             ->withOption('2', $this->plugin->txt('settings_supervision_radiobutton_option_none'))
                                             ->withValue($this->plugin_settings['supervision'] ?? '0')
                                             ->withDisabled($this->protocol_has_entries);
        $section_inputs_supervision = [$rb_sup_onsite];
        return $this->field_factory->section($section_inputs_supervision, $this->plugin->txt('settings_supervision_section_title'));
    }

    private function buildMaterialSection(): Section
    {
        $rb_material_book = $this->field_factory->radio($this->plugin->txt('settings_material_radiobutton_title'))
                                                ->withOption(
                                                    '0',
                                                    $this->plugin->txt('settings_material_radiobutton_option_closed_book'),
                                                    $this->plugin->txt('settings_material_radiobutton_option_closed_book_byline')
                                                )
                                                ->withOption(
                                                    '1',
                                                    $this->plugin->txt('settings_material_radiobutton_option_open_book'),
                                                    $this->plugin->txt('settings_material_radiobutton_option_open_book_byline')
                                                )
                                                ->withOption(
                                                    '2',
                                                    $this->plugin->txt('settings_material_radiobutton_option_other')
                                                )
            // $this->plugin->txt('settings_material_radiobutton_option_other_byline'))
                                                ->withValue($this->plugin_settings['exam_policy'] ?? 0)
                                                ->withDisabled($this->protocol_has_entries);
        $ta_desc_material = $this->field_factory->textarea($this->plugin->txt('settings_material_textarea_additional_information_title'))
                                                ->withValue($this->plugin_settings['exam_policy_desc'] ?? '')
                                                ->withDisabled($this->protocol_has_entries);
        $section_inputs_materials = [
            $rb_material_book,
            $ta_desc_material
        ];
        return $this->field_factory->section($section_inputs_materials, $this->plugin->txt('settings_material_section_title'));
    }

    private function buildLocationSection(): Section
    {
        $rb_location = $this->field_factory->radio($this->plugin->txt('settings_location_radiobutton_title'))
                                           ->withOption('0', $this->plugin->txt('settings_location_radiobutton_option_on_premise'))
                                           ->withOption('1', $this->plugin->txt('settings_location_radiobutton_option_remote'))
                                           ->withValue($this->plugin_settings['location'] ?? '0')
                                           ->withDisabled($this->protocol_has_entries);
        $section_inputs_location = [$rb_location];
        return $this->field_factory->section($section_inputs_location, $this->plugin->txt('settings_location_section_title'));
    }

    protected function saveContent(): void
    {
        $data = $this->form->getData();
        $values = [
            ['integer', $this->test_object->test_id],
            ['text',    $data[0][0]],
            ['text',    $data[0][1]],
            ['integer', $data[1][0]],
            ['integer', $data[1][1][0]],
            ['text',    $data[1][1][1][0] ?? ''],
            ['integer', $data[2][0]],
            ['integer', $data[3][0]],
            ['text',    $data[3][1]],
            ['integer', $data[4][0]],
            ['text',    '']
        ];
        if ($this->db_connector->settingsExistByTestID($this->test_object->test_id)) {
            $where = [
                $this->db_connector::TEST_ID_KEY => ['integer', $this->test_object->test_id]
            ];
            $this->db_connector->updateSetting($values, $where);
        } else {
            $this->db_connector->insertSetting($values);
        }
        $this->ctrl->setParameterByClass(self::class, 'Success', true );
        $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(self::class, self::CMD_SHOW));
    }

    protected function deleteContent()
    {
    }
}
