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

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolEventTableGUI extends ilTable2GUI
{
    /** @var ilExaminationProtocolPlugin */
    protected $plugin;

    public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
    {
        global $DIC;
        $ctrl = $DIC['ilCtrl'];
        $this->setId("texa_event_protocol");
        $this->setFormName('form_texa_event_protocol');
        $this->setFormAction($ctrl->getFormAction($a_parent_obj));
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->createTable();
    }

    protected function createTable(): void
    {
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        $this->setTitle($this->plugin->txt('protocol_tab_name'));
        $this->setRowTemplate('tpl.protocol_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        $this->setLimit(5000);
        $this->addColumn($this->plugin->txt("event_table_column_start"), "start");
        $this->addColumn($this->plugin->txt("event_table_column_end"), "end");
        $this->addColumn($this->plugin->txt("event_table_column_typ"), "event_type");
        $this->addColumn($this->plugin->txt("event_table_column_event"), "comment");
        $this->addColumn($this->plugin->txt("event_table_column_location"), "location");
        $this->addColumn($this->plugin->txt("event_table_column_student_id"), "student_id");
        $this->addColumn($this->plugin->txt("event_table_column_supervisor_id"), "supervisor");
        foreach ($this->getSelectedColumns() as $column) {
            $this->addColumn($this->getSelectableColumns()[$column]['txt'], $column);
        }
        $this->addColumn($this->plugin->txt("event_table_column_actions"), "", 90);
        $this->setDefaultOrderField("start");
        $this->setDefaultOrderDirection("asc");

    }

    public function getSelectableColumns(): array
    {
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        return [
            "last_edit" => ["txt" => $this->plugin->txt("event_table_column_timestamp_edit"), "a_sort_field" => 'last_edit', "default" => false],
            "last_edited_by" => ["txt" => $this->plugin->txt("event_table_column_supervisor_id_edit"), "a_sort_field" => 'last_edited_by', "default" => false],
            "creation" => ["txt" => $this->plugin->txt("event_table_column_timestamp"), "a_sort_field" => 'creation', "default" => false],
            "created_by" => ["txt" => $this->plugin->txt("event_table_column_creator_id"), "a_sort_field" => 'created_by', "default" => false],
        ];
    }

    public function fillRow($a_set): void
    {
        $columns = [
            'START' => ilDatePresentation::formatDate(new \ilDateTime(strtotime($a_set['start']), IL_CAL_UNIX)),
            'END' => ilDatePresentation::formatDate(new \ilDateTime($a_set['end'], IL_CAL_DATETIME)),
            'EVENT_TYPE' => $a_set['event_type'],
            'COMMENT' => $a_set['comment'],
            'LOCATION' => $a_set['location'],
            'STUDENT_ID' => $a_set['student_id'],
            'SUPERVISOR' => $a_set['supervisor'],
        ];
        foreach ($this->getSelectedColumns() as $column) {
            switch ($column) {
                case 'last_edit':
                    $columns['LAST_EDIT'] = ilDatePresentation::formatDate(new \ilDateTime($a_set['last_edit'], IL_CAL_DATETIME));
                    break;
                case 'last_edited_by':
                    $columns['LAST_EDITED_BY'] = $a_set['last_edited_by'];
                    break;
                case 'creation':
                    $columns['CREATION'] = ilDatePresentation::formatDate(new \ilDateTime($a_set['creation'], IL_CAL_DATETIME));
                    break;
                case 'created_by':
                    $columns['CREATED_BY'] = $a_set['created_by'];
                    break;
            }
        }
        $columns['ACTION'] = $a_set['action'];
        foreach ($columns as $column => $cell) {
            if (in_array($column, $this->getSelectedColumns())) {
                $this->tpl->setCurrentBlock($column);
                $this->tpl->setVariable($column, $cell);
                $this->tpl->parseCurrentBlock();
            } else {
                $this->tpl->setVariable($column, $cell);
            }
        }
    }

}
