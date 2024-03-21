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

use Exception;
use ilExaminationProtocolPlugin;

/**
 * TODO Refactor out the HTML
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolHTMLBuilder
{
    private ?ilExaminationProtocolPlugin $plugin;
    private ilExaminationProtocolDBConnector $db_connector;
    private array $properties;
    private array $supervisors;
    private array $locations;
    private array $protocol;

    public function __construct(int $test_id)
    {
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->db_connector = new ilExaminationProtocolDBConnector();
        $protocol_id = intval($this->db_connector->getProtocolIDByTestID($test_id));
        $this->properties = $this->db_connector->getSettingByTestID($test_id);
        $this->protocol = $this->db_connector->getAllProtocolEntriesByProtocolID($protocol_id);
        $this->supervisors = $this->db_connector->getAllSupervisorsByProtocolID($protocol_id);
        $this->locations = $this->db_connector->getAllLocationsByProtocolID($protocol_id);
    }

    public function getHTML(): string
    {
        return $this->buildPage();
    }

    /**
     * @throws Exception
     */
    private function buildPage(): string
    {
        $header = $this->buildHeader();
        $page = $this->buildBodyInfo();
        $html_table = $this->buildBodyTable();

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
    private function buildHeader(): string
    {
        $test_title = $this->properties['protocol_title'];
        return "<title>" . $test_title . "</title>";
    }

    private function buildBodyInfo(): string
    {
        $html = '';
        $no_entry = $this->plugin->txt("no_entry");
        $today = date("d.m.Y H:i",strtotime("now"));
        $test_title = $this->properties['protocol_title'] ?: $no_entry;
        $protocol_description = $this->properties['protocol_desc'] ?: $no_entry;
        $type_description = $this->properties['type_desc'] ?: $no_entry;
        $exam_policy_desc = $this->properties['exam_policy_desc'] ?: $no_entry;
        $locations_line = '';
        $supervisor_line = '';

        if ($this->properties['type_exam'] == 0) {
            $type_text = $this->plugin->txt('settings_examination_type_radiobutton_option_online');
        } else {
            $type_text = $this->plugin->txt('settings_examination_type_radiobutton_option_upload');
        }
        if ($this->properties['type_only_ilias'] == 0) {
            $tool_text = $this->plugin->txt('settings_examination_type_radiobutton_software_only_ilias_line');
        } else {
            $tool_text = $this->plugin->txt('settings_examination_type_radiobutton_software_additional_software_line');
        }
        $html .= " <h1>" . $test_title . " [obj_id: ". $this->properties['test_id'] ."] </h1> 
                   " . $this->plugin->txt('protocol_creation_date') . ": " . $today . "  </br>
                   " . $this->plugin->txt('description') . ": " . $protocol_description . " </br>
                   " . $this->plugin->txt('settings_examination_type_radiobutton_title') . ": " . $type_text . " </br>
                   " . $this->plugin->txt('settings_examination_type_radiobutton_software_title') . ": " . $tool_text . "</br>";
        if ($this->properties['type_only_ilias'] == 1) {
            $html .= $this->plugin->txt('settings_examination_type_textarea_additional_software_title') . ": " . $type_description . "</br>";
        }
        if ($this->properties['supervision'] == 0) {
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_onsite');
        } elseif ($this->properties['supervision'] == 1){
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_remote');
        } else {
            $supervision_text = $this->plugin->txt('settings_supervision_radiobutton_option_none');
        }
        if ($this->properties['exam_policy'] == 0) {
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_closed_book');
        } elseif ($this->properties['exam_policy'] == 1){
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_open_book');
        } else {
            $exam_policy_text = $this->plugin->txt('settings_material_radiobutton_option_other');
        }
        if ($this->properties['location'] == 0) {
            $location_text = $this->plugin->txt('settings_location_radiobutton_option_on_premise');
            $locations_line = $this->plugin->txt('html_location') . ": ";
            $locations_list = '';
            foreach ($this->locations as $location) {
                $locations_list .= $location['location'];
                if (end($this->locations) != $location){
                    $locations_list .= ", ";
                }
            }
            $locations_line .= $locations_list ?: $no_entry;
            $locations_line .= "</br>";
        } else {
            $location_text = $this->plugin->txt('settings_location_radiobutton_option_remote');
        }
        $html .= $this->plugin->txt('settings_supervision_section_title') . ": " . $supervision_text . " </br>";
        if ($this->properties['supervision'] != 2) {
            $supervisor_line = $this->plugin->txt('html_supervisors') . ": ";
            $supervisor_list = '';
            foreach ($this->supervisors as $sups) {
                $supervisor_list .= $sups['name'];
                if (end($this->supervisors) != $sups) {
                    $supervisor_list  .= ", ";
                }
            }
            $supervisor_line .= $supervisor_list ?: $no_entry;
            $supervisor_line .= "</br>";
        }
        $html .= $supervisor_line;
        $html .= $this->plugin->txt('settings_material_radiobutton_title') . ": " . $exam_policy_text . " </br>
                  " . $this->plugin->txt('settings_material_textarea_additional_information_title') . ": " . $exam_policy_desc. " </br>
                  " . $this->plugin->txt('settings_location_section_title') . ": " . $location_text . "</br>";
        if (!empty($locations_line)) {
            $html .= $locations_line;
        }
        return $html;
    }

    private function buildBodyTable(): string
    {
        $event_options = ilExaminationProtocolEventEnumeration::getAllOptionsInLanguage();
        $htmlTable = "<table border='1'>
                <thead>
                    <tr align='left'>
                        <th>".$this->plugin->txt('event_table_column_start')."</th>
                        <th>".$this->plugin->txt('event_table_column_end')."</th>
                        <th>".$this->plugin->txt('event_table_column_student_id')."</th>
                        <th>".$this->plugin->txt('event_table_column_typ')."</th>
                        <th>".$this->plugin->txt('event_table_column_event')."</th>
                        <th>".$this->plugin->txt('event_table_column_location')."</th>
                        <th>".$this->plugin->txt('event_table_column_supervisor_id')."</th>
                        <th>".$this->plugin->txt('event_table_column_timestamp_edit')."</th>
                        <th>".$this->plugin->txt('event_table_column_supervisor_id_edit')."</th>
                        <th>".$this->plugin->txt('event_table_column_timestamp')."</th>
                        <th>".$this->plugin->txt('event_table_column_creator_id')."</th>
                    </tr>
                </thead>
                <tbody>";

        usort($this->protocol, function ($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });

        foreach ($this->protocol as $row) {
            $student_ids = '';
            $participants = $this->db_connector->getAllProtocolParticipants(intval($row['entry_id']));
            foreach ($participants as $participant) {
                $usr_id = $this->db_connector->getUserIDbyParticipantID(intval($participant['participant_id']));
                if (isset($usr_id[0]['usr_id'])){
                    $il_user_id = intval($this->db_connector->getUserIDbyParticipantID(intval($participant['participant_id']))[0]['usr_id']);
                    $matriculation = $this->db_connector->getMatriculationByUserID($il_user_id)[0]['matriculation'];
                    $res = $this->db_connector->getUsernameByUserID($il_user_id)[0];
                    if ($matriculation == '') {
                        $matriculation = '--';
                    }
                    $student_ids.= $res['lastname'] . ", " . $res['firstname'] . "(".$matriculation .", [".$res['login']."])</br>" ;
                }
            }
            if ($row['supervisor_id'] != 0) {
                $responsible_supervisor = $this->db_connector->getSupervisorBySupervisorID(intval($row['supervisor_id']))[0]['name'];
            } else {
                $responsible_supervisor = $this->plugin->txt('entry_dropdown_supervisor_no_supervisor');
            }

            if ($this->properties['location'] == '0' and $row['location_id'] != 0) {
                $location = $this->db_connector->getLocationsByLocationID(intval($row['location_id']))[0]['location'];
            } else {
                $location = $this->plugin->txt('entry_dropdown_location_no_location');
            }
            $editor = $this->db_connector->getLoginByUserID(intval($row['last_edited_by']))[0]['login'];
            $creator = $this->db_connector->getLoginByUserID(intval($row['last_edited_by']))[0]['login'];
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
                    <td>" . $creation_datetime->get(IL_CAL_DATETIME) . "</td>
                    <td>" . $creator . "</td>
                   </tr>";
        }

        $htmlTable .= "</tbody></table>";
        return $htmlTable;
    }
}
