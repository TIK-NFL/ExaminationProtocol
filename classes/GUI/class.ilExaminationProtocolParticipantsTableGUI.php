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
class ilExaminationProtocolParticipantsTableGUI extends ilTable2GUI
{
    /** @var ilExaminationProtocolPlugin */
    protected $plugin;
    /** @var array */
    public $current_filter;

    /**
     * @param object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     */
    public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->setId("texa_participant");
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        // title
        $this->setTitle($this->plugin->txt('participant_table_title'));
        $this->setFormName('form_texa_participant');

        // default no entries set
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);

        // selector
        $this->setShowRowsSelector(true);
        $this->setSelectAllCheckbox('participant');
        $this->addMultiCommand("delete", $this->lng->txt('delete'));

        // row template
        $this->setRowTemplate('tpl.participant_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());

        // filter
        $this->initFilter();

        // unsure
        $this->enable('sort');
        $this->enable('header');
        $this->enable('numinfo');
        $this->enable('select_all');
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));

        // build Table
        $this->addColumn('', 'participant', '1px', true);
        $this->addColumn($this->plugin->txt("participant_table_column_name"), 'name');
        $this->addColumn($this->plugin->txt("participant_table_column_login"), 'login');
        $this->addColumn($this->plugin->txt("participant_table_column_mrt"), 'matriculation');
        $this->addColumn($this->plugin->txt("participant_table_column_email"), 'email');
        // ordering
        $this->setDefaultOrderField("matriculation");
        $this->setDefaultOrderDirection("asc");
    }

    public function initFilter() : void
    {
        $this->setDefaultFilterVisiblity(true);

        $name = $this->addFilterItemByMetaType(
            'name',
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->lng->txt('name')
        );
        $this->current_filter['name'] = $name->getValue();

        $login = $this->addFilterItemByMetaType(
            'login',
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->lng->txt('login')
        );
        $this->current_filter['login'] = $login->getValue();

        $mrt = $this->addFilterItemByMetaType(
            'matriculation',
            ilTable2GUI::FILTER_TEXT,
            false,
            $this->lng->txt('matriculation')
        );
        $this->current_filter['matriculation'] = $mrt->getValue();
    }

    /**
     * fills an array into the tables
     * @param array $a_set
     * @return void
     */
    protected function fillRow($a_set) : void
    {
        $checkbox = ilUtil::formCheckbox(false, 'participants[]', $a_set['participant_id']);

        parent::fillRow([
            'CHECKBOX' => $checkbox,
            'NAME' => $a_set['name'],
            'LOGIN' => $a_set['login'],
            'MATRICULATION' => $a_set['matriculation'],
            'EMAIL' => $a_set['email'],
        ]);
    }
}
