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

use DateTime;
use DateTimeZone;
use Exception;
use ilExaminationProtocolPlugin;


/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolHTMLBuilder
{
    private ?ilExaminationProtocolPlugin $plugin;
    private ilExaminationProtocolDBConnector $db_connector;

    public function __construct()
    {
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->db_connector = new ilExaminationProtocolDBConnector();
    }

    public function getHTML(array $properties ,array $protocol) : string
    {
        return $this->buildPage($properties ,$protocol);
    }

    private function buildPage(array $properties ,array $protocol) : string
    {
        $today = date("d.m.Y H:i",strtotime("now"));
        $html_table = $this->buildTable($properties, $protocol);
        $test_title = $this->db_connector->getTestTitleById($properties['test_id'])['title'];
        // HTML page creation
        $html_Page = "<!DOCTYPE html>
                     <html>
                        <head>
                           <title>" . $test_title . "</title>
                        </head>
                        <body>
                           <h1>" . $test_title . " [obj_id: ". $properties['test_id'] ."]</h1>
                           <p>" . $this->plugin->txt('protocol_creation_date') . ": " . $today . "</p>
                           " . $html_table . " 
                           </body>
                     </html>";
        return $html_Page;
    }

    private function buildTable(array $properties, array $table_content) : string
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
                if (isset($usr_id[0]['usr_id'])){
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

            $htmlTable .= "<tr>
                    <td>" . $this->utctolocal($row['start']) . "</td>
                    <td>" . $this->utctolocal($row['end']) . "</td>
                    <td>" . $student_ids . "</td>
                    <td>" . $event_options[$row['event']] . "</td>
                    <td>" . $row['comment'] . "</td>
                    <td>" . $location . "</td>
                    <td>" . $responsible_supervisor. "</td>
                    <td>" . $this->utctolocal($row['last_edit']) . "</td>
                    <td>" . $editor  . "</td>
                    <td>" . $this->utctolocal($row['creation'])  . "</td>
                    <td>" . $creator . "</td>
                   </tr>";
        }

        $htmlTable .= "</tbody></table>";
        return $htmlTable;
    }

    /**
     * @throws Exception
     */
    public function utctolocal(string $time) : string
    {
        $loc = (new DateTime)->getTimezone();
        $time = new DateTime($time, new DateTimeZone('UTC'));
        $time->setTimezone($loc);
        return $time->format("d.m.Y H:i");
    }
}
