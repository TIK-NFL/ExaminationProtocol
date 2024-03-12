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

namespace ILIAS\Plugin\ExaminationProtocol;

use ilExaminationProtocolPlugin;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolHTMLBuilder
{
    /** @var ilExaminationProtocolPlugin|null  */
    private $plugin;
    /** @var ilExaminationProtocolDBConnector  */
    private $db_connector;

    public function __construct()
    {
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->db_connector = new ilExaminationProtocolDBConnector();
    }

    public function getHTML(array $properties ,array $protocol): string
    {
        return $this->buildPage($properties ,$protocol);
    }

    private function buildPage(array $properties ,array $protocol): string
    {
        $header = $this->buildHeader($properties);
        $page = $this->buildBodyInfo($properties);
        $html_table = $this->buildBodyTable($properties, $protocol);

        // TODO replace with Template engine as soon as there are less revisions of the content
        return "<!DOCTYPE html>
                     <html>
                        <head>
                           " . $header . "
                        </head>
                        <body>
                           " . $page . " </br>
                           " . $html_table . " 
                           </body>
                     </html>";
    }

    /**
     * @throws \ilDateTimeException
     */
    private function buildBodyInfo(array $properties): string
    {
        $html = '';

        $today =  new \ilDate(time(), IL_CAL_UNIX);
        $test_title = $this->db_connector->getTestTitleById($properties['test_id'])['title'];
        $settings = $this->db_connector->getSettingByTestID($properties['test_id']);
        if ($settings['type_exam'] == 0) {
            $type_text = $this->plugin->txt('settings_examination_type_radiobutton_option_online');
        } else {
            $type_text = $this->plugin->txt('settings_examination_type_radiobutton_option_upload');
        }
        if ($settings['type_only_ilias'] == 0) {
            $tool_text = $this->plugin->txt('settings_examination_type_radiobutton_software_only_ilias_line');
        } else {
            $tool_text = $this->plugin->txt('settings_examination_type_radiobutton_software_additional_software_line');
        }
        $html .= " <h1>" . $test_title . " [obj_id: ". $properties['test_id'] ."]</h1>
                   <p>" . $this->plugin->txt('protocol_creation_date') . ": " . $today . "</p>
                   <p>" . $this->plugin->txt('description') . ": " . $settings['protocol_desc'] . "</p>
                   <p>" . $this->plugin->txt('settings_examination_type_radiobutton_title') . ": " . $type_text . "</p>
                   <p>" . $this->plugin->txt('settings_examination_type_radiobutton_software_title') . ": " . $tool_text . "</p>";
        if ($settings['type_only_ilias'] == 1) {
            $html .= "<p>" . $this->plugin->txt('settings_examination_type_textarea_additional_software_title') . ": " . $settings['type_desc'] . "</p>";
        }
        if ($settings['supervision'] == 0) {
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_onsite');
        } elseif ($settings['supervision'] == 1){
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_remote');
        } else {
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_none');
        }
        if ($settings['exam_policy'] == 0) {
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_closed_book');
        } elseif ($settings['exam_policy'] == 1){
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_open_book');
        } else {
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_other');
        }
        if ($settings['location'] == 0) {
            $location_text = $this->plugin->txt('settings_location_radiobutton_option_on_premise');
        } else {
            $location_text = $this->plugin->txt('settings_location_radiobutton_option_remote');
        }
        $html .=  "<p>" . $this->plugin->txt('settings_supervision_section_title') . ": " . $supervision_text . "</p>
                   <p>" . $this->plugin->txt('settings_material_radiobutton_title') . ": " . $exam_policy_text . "</p>
                   <p>" . $this->plugin->txt('settings_material_textarea_additional_information_title') . ": " . $settings['exam_policy_desc'] . "</p>
                   <p>" . $this->plugin->txt('settings_location_section_title') . ": " . $location_text . "</p>";
        return $html;
    }

    private function buildHeader(array $properties): string
    {
        $test_title = $this->db_connector->getTestTitleById($properties['test_id'])['title'];
        return "<title>" . $test_title . "</title>";

    }

    /**
     * @throws \ilDateTimeException
     */
    private function buildBodyTable(array $properties, array $table_content): string
    {
        $event_options = ilExaminationProtocolEventEnumeration::getAllOptionsInLanguage($this->plugin);
        $htmlTable = "<table border='1'>
                <thead>
                    <tr align='left'>
                        <th>".$this->plugin->txt('event_table_column_start')."</th>
                        <th>".$this->plugin->txt('event_table_column_end')."</th>
                        <th>".$this->plugin->txt('event_table_column_student_id')."</th>
                        <th>".$this->plugin->txt('event_table_column_typ')."</th>
                        <th>".$this->plugin->txt('description')."</th>
                        <th>".$this->plugin->txt('event_table_column_location')."</th>
                        <th>".$this->plugin->txt('event_table_column_supervisor_id')."</th>
                        <th>".$this->plugin->txt('event_table_column_timestamp_edit')."</th>
                        <th>".$this->plugin->txt('event_table_column_supervisor_id_edit')."</th>
                        <th>".$this->plugin->txt('event_table_column_timestamp')."</th>
                        <th>".$this->plugin->txt('event_table_column_creator_id')."</th>
                    </tr>
                </thead>
                <tbody>";
        usort($table_content, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        foreach ($table_content as $row) {
            $student_ids = "";
            $participants = $this->db_connector->getAllProtocolParticipants($row['entry_id']);
            foreach ($participants as $participant) {
                $usr_id = $this->db_connector->getUserIDbyParticipantID($participant['participant_id']);
                if (isset($usr_id[0]['usr_id'])) {
                    $il_user_id = $this->db_connector->getUserIDbyParticipantID($participant['participant_id'])[0]['usr_id'];
                    $matriculation = $this->db_connector->getMatriculationByUserID($il_user_id)[0]['matriculation'];
                    $res = $this->db_connector->getUsernameByUserID($il_user_id)[0];
                    if ($matriculation == '') {
                        $matriculation = '--';
                    }
                    $student_ids.= $res['lastname'] . ", " . $res['firstname'] . "(".$matriculation .", [".$res['login']."])</br>" ;
                }
            }
            if ($row['supervisor_id'] != 0) {
                $responsible_supervisor = $this->db_connector->getSupervisorBySupervisorID($row['supervisor_id'])[0]['name'];
            } else {
                $responsible_supervisor = $this->plugin->txt('entry_dropdown_supervisor_no_supervisor');
            }

            if ($properties['location'] == '0' and $row['location_id'] != 0) {
                $location = $this->db_connector->getLocationsByLocationID($row['location_id'])[0]['location'];
            } else {
                $location = $this->plugin->txt('entry_dropdown_location_no_location');
            }
            $editor = $this->db_connector->getLoginByUserID($row['last_edited_by'])[0]['login'];
            $creator = $this->db_connector->getLoginByUserID($row['last_edited_by'])[0]['login'];

            $start_datetime = new \ilDateTime($row['start'], IL_CAL_DATETIME);
            $end_datetime = new \ilDateTime($row['end'], IL_CAL_DATETIME);
            $last_edit_datetime = new \ilDateTime($row['last_edit'], IL_CAL_DATETIME);
            $creation_datetime = new \ilDateTime($row['creation'], IL_CAL_DATETIME);

            $htmlTable .= "<tr>
                    <td>" . $start_datetime->get(IL_CAL_DATETIME) . "</td>
                    <td>" . $end_datetime->get(IL_CAL_DATETIME) . "</td>
                    <td>" . $student_ids . "</td>
                    <td>" . $event_options[$row['event']] . "</td>
                    <td>" . $row['comment'] . "</td>
                    <td>" . $location . "</td>
                    <td>" . $responsible_supervisor. "</td>
                    <td>" . $last_edit_datetime->get(IL_CAL_DATETIME) . "</td>
                    <td>" . $editor  . "</td>
                    <td>" . $creation_datetime->get(IL_CAL_DATETIME)  . "</td>
                    <td>" . $creator . "</td>
                   </tr>";
        }
        $htmlTable .= "</tbody></table>";
        return $htmlTable;
    }
}
