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
class ilExaminationProtocolSupervisorTableGUI extends ilTable2GUI
{
    protected ilExaminationProtocolPlugin $plugin;
    private bool $disabled;

    /**
     * @param object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @param bool   $disabled
     * @throws ilException
     */
    public function __construct($a_parent_obj, string $a_parent_cmd = "", string $a_template_context = "", bool $disabled = false)
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->disabled = $disabled;
        $this->setId('texa_supervisor');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->setTitle($this->plugin->txt('supervisor_table_title'));
        $this->setFormName('formSupervisor');
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        $this->setRowTemplate('tpl.supervisor_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));

        if (!$this->disabled) {
            $this->setShowRowsSelector(true);
            $this->setSelectAllCheckbox('supervisors');
            $this->addMultiCommand('delete', $this->lng->txt('delete'));
        }
        $this->addColumn('', 'supervisor_id', '1px', true);
        $this->addColumn($this->plugin->txt("supervisor_table_column_name"), 'name');
    }

    /**
     * fills an array into the tables
     *
     * @param array $a_set
     * @return void
     */
    protected function fillRow(array $a_set) : void
    {
        $checkbox = '';
        $type = 'hidden';
        if (!$this->disabled) {
            $type = 'checkbox';
            $checkbox = $a_set['supervisor_id'];
        }
        parent::fillRow([
            'TYPE' => $type,
            'CHECKBOX' => $checkbox,
            'NAME' => $a_set['name'],
        ]);
    }
}
