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
 * @ilCtrl_isCalledBy ilExaminationProtocolGeneralSettingsGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilExaminationProtocolGeneralSettingsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI
 */
class ilExaminationProtocolGeneralSettingsGUI extends ilExaminationProtocolBaseController
{
    /** @var mixed */
    private $form;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        parent::__construct();
        $this->buildForm();
    }

    private function buildForm() : void
    {
        // tab
        $this->tabs->activateSubTab(self::GENERAL_SETTINGS_TAB_ID);

        // info
        if ($this->protocol_has_entries) {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt("lock"));
        }

        // General Settings
        $text_title = $this->field_factory->text($this->plugin->txt("settings_general_settings_text_title"))
            ->withValue($this->settings['protocol_title'] ?? $this->test_object->getTitle())
            ->withRequired(true)
            ->withDisabled($this->protocol_has_entries);
        $ta_desc = $this->field_factory->textarea($this->lng->txt("desc"))
            ->withValue($this->settings['protocol_desc'] ?? "")
            ->withDisabled($this->protocol_has_entries);
        $sectionInputsGeneral = [
            $text_title,
            $ta_desc
        ];
        $section_general = $this->field_factory->section($sectionInputsGeneral, $this->plugin->txt("sub_tab_settings"));

        // Type of Examination
        // online Upload
        $rb_type = $this->field_factory->radio($this->plugin->txt("settings_examination_type_radiobutton_title"))
            ->withOption("0", $this->plugin->txt("settings_examination_type_radiobutton_option_online"))
            ->withOption("1", $this->plugin->txt("settings_examination_type_radiobutton_option_upload"))
            ->withValue($this->settings['type_exam'] ?? "0")
            ->withDisabled($this->protocol_has_entries);

        // Used TOOLS space

        // Only ILIAS
        $g_software_ilias = $this->field_factory->group(
            [],
            $this->plugin->txt("settings_examination_type_radiobutton_software_only_ilias_line")
        );

        // text area additional software
        $ta_software = $this->field_factory->textarea(
            $this->plugin->txt("settings_examination_type_textarea_additional_software_title")
        )
            ->withValue($this->settings['type_desc'] ?? "")
            ->withByline($this->plugin->txt("settings_examination_type_radiobutton_software_additional_software_byline"));

        // group software
        $g_Software_add = $this->field_factory->group(
            [ 0 => $ta_software],
            $this->plugin->txt("settings_examination_type_radiobutton_software_additional_software_line")
        );

        // switchable group the "radiobutton" for the different groups
        $sg_software = $this->field_factory->switchableGroup(
            [$g_software_ilias, $g_Software_add],
            $this->plugin->txt("settings_examination_type_radiobutton_software_title")
        )
            ->withValue($this->settings['type_only_ilias'] ?? "0")
            ->withDisabled($this->protocol_has_entries); // BROKEN in KITCHENSINK....

        $section_inputs_examination = [
            $rb_type,
            $sg_software
        ];
        $section_examination = $this->field_factory->section($section_inputs_examination, $this->plugin->txt("settings_examination_type_section_type_title"));

        // Type of supervision
        $rb_sup_onsite = $this->field_factory->radio($this->plugin->txt("settings_supervision_radiobutton_supervision_title"))
            ->withOption("0", $this->plugin->txt("settings_supervision_radiobutton_option_onsite"))
            ->withOption("1", $this->plugin->txt("settings_supervision_radiobutton_option_remote"))
            ->withOption("2", $this->plugin->txt("settings_supervision_radiobutton_option_none"))
            ->withValue($this->settings['supervision'] ?? "0")
            ->withDisabled($this->protocol_has_entries);

        $section_inputs_supervision = [
            $rb_sup_onsite,
        ];
        $section_supervision = $this->field_factory->section($section_inputs_supervision, $this->plugin->txt("settings_supervision_section_title"));

        // Allowed references and materials
        $rb_material_book = $this->field_factory->radio($this->plugin->txt("settings_material_radiobutton_title"))
            ->withOption(
                "0",
                $this->plugin->txt("settings_material_radiobutton_option_closed_book"),
                $this->plugin->txt("settings_material_radiobutton_option_closed_book_byline")
            )
            ->withOption(
                "1",
                $this->plugin->txt("settings_material_radiobutton_option_open_book"),
                $this->plugin->txt("settings_material_radiobutton_option_open_book_byline")
            )
            ->withOption(
                "2",
                $this->plugin->txt("settings_material_radiobutton_option_other")
            )
             // $this->plugin->txt("settings_material_radiobutton_option_other_byline"))
            ->withValue($this->settings['exam_policy'] ?? 0)
            ->withDisabled($this->protocol_has_entries);

        $ta_desc_material = $this->field_factory->textarea($this->plugin->txt("settings_material_textarea_additional_information_title"))
            ->withValue($this->settings['exam_policy_desc'] ?? "")
            ->withDisabled($this->protocol_has_entries);

        $section_inputs_materials = [
            $rb_material_book,
            $ta_desc_material
        ];
        $section_material = $this->field_factory->section($section_inputs_materials, $this->plugin->txt("settings_material_section_title"));

        // section location
        $rb_location = $this->field_factory->radio($this->plugin->txt("settings_location_radiobutton_title"))
        ->withOption("0", $this->plugin->txt("settings_location_radiobutton_option_on_premise"))
        ->withOption("1", $this->plugin->txt("settings_location_radiobutton_option_remote"))
        ->withValue($this->settings['location'] ?? "0")
        ->withDisabled($this->protocol_has_entries);


        $section_inputs_location = [
            $rb_location,
        ];
        $section_location = $this->field_factory->section($section_inputs_location, $this->plugin->txt("settings_location_section_title"));
        // complete form
        $site = [
            $section_general,
            $section_examination,
            $section_supervision,
            $section_material,
            $section_location
        ];

        $form_action = $this->ctrl->getFormAction($this, self::CMD_SHOW);
        if (!$this->protocol_has_entries) {
            $form_action = $this->ctrl->getFormAction($this, self::CMD_SAVE);
        }

        $this->form = $this->ui_factory->input()->container()->form()->standard($form_action, $site);
        if ($this->request->getMethod() == "POST") {
            $this->form = $this->form->withRequest($this->request);
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
                $this->getHTML();
                break;
        }
    }

    public function getHTML() : string
    {
        return $this->renderer->render($this->form);
    }

    protected function save() : void
    {
        $data = $this->form->getData();
        // build input Array
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

        // update Database
        if ($this->db_connector->settingsExistByTestID($this->test_object->test_id)) {
            $where = [
                $this->db_connector::TEST_ID_KEY => ['integer', $this->test_object->test_id]
            ];
            $this->db_connector->updateSetting($values, $where);
        } else {
            $this->db_connector->insertSetting($values);
        }
    }
}
