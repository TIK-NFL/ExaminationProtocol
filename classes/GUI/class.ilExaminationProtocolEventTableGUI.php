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
    protected ilExaminationProtocolPlugin $plugin;

    /**
     * @param object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @throws ilException
     */
    public function __construct(object $a_parent_obj, string $a_parent_cmd = "", string$a_template_context = "")
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

    /**
     * Creates the table with columns
     * @return void
     */
    protected function createTable() : void
    {
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        $this->setTitle($this->plugin->txt('protocol_tab_name'));
        $this->setRowTemplate('tpl.protocol_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        $this->setLimit(5000);
        $this->addColumn($this->plugin->txt("event_table_column_start"), "start");
        $this->addColumn($this->plugin->txt("event_table_column_end"), "end");
        $this->addColumn($this->plugin->txt("event_table_column_typ"), "event_type");
        $this->addColumn($this->plugin->txt("description"), "comment");
        $this->addColumn($this->plugin->txt("event_table_column_location"), "location");
        $this->addColumn($this->plugin->txt("event_table_column_student_id"), "student_id");
        $this->addColumn($this->plugin->txt("event_table_column_student_names"), "student_names");
        $this->addColumn($this->plugin->txt("event_table_column_supervisor_id"), "supervisor");
        foreach ($this->getSelectedColumns() as $column) {
            $this->addColumn($this->getSelectableColumns()[$column]['txt'], $column);
        }
        $this->addColumn($this->plugin->txt("event_table_column_actions"), '', '90');

        $this->setDefaultOrderField("start");
        $this->setDefaultOrderDirection("asc");
    }

    /**
     * @return array[]
     */
    public function getSelectableColumns() : array
    {
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        return [
            "edit_tstamp" => ["txt" => $this->plugin->txt("event_table_column_timestamp_edit"), "default" => false],
            "edit_user" => ["txt" => $this->plugin->txt("event_table_column_supervisor_id_edit"), "default" => false],
            "creation_tstamp" => ["txt" => $this->plugin->txt("event_table_column_timestamp"), "default" => false],
            "creation_user" => ["txt" => $this->plugin->txt("event_table_column_creator_id"), "default" => false],
        ];
    }

    /**
     * @param array $a_set
     * @return void
     * @throws ilTemplateException
     * @throws Exception
     */
    public function fillRow(array $a_set) : void
    {
        $columns = [
            'START' => $a_set['start'],
            'END' => $a_set['end'],
            'EVENT_TYPE' => $a_set['event_type'],
            'COMMENT' => $a_set['comment'],
            'LOCATION' => $a_set['location'],
            'STUDENT_ID' => $a_set['student_id'],
            'SUPERVISOR' => $a_set['supervisor'],
        ];
        foreach ($this->getSelectedColumns() as $column) {
            switch (strtoupper($column)) {
                case 'EDIT_TSTAMP':
                    $columns['EDIT_TSTAMP'] = date('d.m.y H:i', strtotime($a_set['last_edit']));
                    break;
                case 'EDIT_USER':
                    $columns['EDIT_USER'] = $a_set['last_edited_by'];
                    break;
                case 'CREATION_TSTAMP':
                    $columns['CREATION_TSTAMP'] = date('d.m.y H:i', strtotime($a_set['creation']));
                    break;
                case 'CREATION_USER':
                    $columns['CREATION_USER'] = $a_set['created_by'];
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
