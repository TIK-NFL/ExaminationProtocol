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
class ilExaminationProtocolExportTableGUI extends ilTable2GUI
{
    protected ?ilExaminationProtocolPlugin $plugin;

    public function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '')
    {
        global $DIC;
        $ctrl = $DIC['ilCtrl'];
        $this->setId('texa_export');
        $this->setFormName('form_texa_export');
        $this->setFormAction($ctrl->getFormAction($a_parent_obj));
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->buildTable();
    }

    protected function buildTable(): void
    {
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        $this->setLimit(5000);
        $this->setTitle($this->plugin->txt('sub_tab_export'));
        $this->setRowTemplate('tpl.export_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        $this->setShowRowsSelector(true);
        $this->setSelectAllCheckbox('version_number');
        $this->addMultiCommand('delete', $this->lng->txt('delete'));
        $this->addColumn('', 'version_number', '1px',true);
        $this->addColumn($this->plugin->txt('file'), 'file');
        $this->addColumn($this->plugin->txt('size'), 'size');
        $this->addColumn($this->plugin->txt('date'), 'date');
        $this->addColumn($this->plugin->txt('download'), 'action');
        $this->setDefaultOrderField('date');
        $this->setDefaultOrderDirection('desc');
    }

    public function fillRow(array $a_set): void
    {
        parent::fillRow([
            'CHECKBOX' => $a_set['version_number'],
            'FILE' => $a_set['file'],
            'SIZE' => $a_set['size'],
            'DATE' => ilDatePresentation::formatDate(new ilDateTime($a_set['date'], IL_CAL_UNIX)),
            'ACTION' => $a_set['action']
        ]);
    }
}
