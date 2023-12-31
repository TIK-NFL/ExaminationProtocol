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
 * @version  $Id$
 */
class ilExaminationProtocolLocationTableGUI extends ilTable2GUI
{
    /** @var ilExaminationProtocolPlugin */
    protected $plugin;
    /** @var bool  */
    private $disabled;

    /**
     * @param        $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @param bool   $disabled
     */
    public function __construct($a_parent_obj, string $a_parent_cmd = "", string $a_template_context = "", bool $disabled = false)
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->disabled = $disabled;
        $this->setId("texa_location");
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        // title
        $this->setTitle($this->plugin->txt('examination_protocol_location_table_title'));
        $this->setFormName('formLocation');
        // default no entries set
        $this->setNoEntriesText($this->plugin->txt('examination_protocol_table_empty'));
        $this->setEnableHeader(true);
        // selector
        if (!$this->disabled) {
            $this->setShowRowsSelector(true);
            $this->setSelectAllCheckbox('locations');
            $this->addMultiCommand("delete", $this->lng->txt('delete'));
        }
        // row template
        $this->setRowTemplate('tpl.location_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());

        // Action
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));

        // build Table
        $this->addColumn('', 'location_id', '1px', true);
        $this->addColumn($this->plugin->txt("location_table_column_location"), 'location');
        // ordering
        $this->setDefaultOrderField("location");
        $this->setDefaultOrderDirection("asc");
    }

    /**
     * fills an array into the tables
     * @param array $a_set
     * @return void
     */
    protected function fillRow($a_set) : void
    {
        $checkbox = "";
        if (!$this->disabled) {
            $checkbox = ilUtil::formCheckbox(false, 'locations[]', $a_set['location_id']);
        }

        parent::fillRow([
            'LOCATION' => $a_set['location'],
            'CHECKBOX' => $checkbox
        ]);
    }
}
