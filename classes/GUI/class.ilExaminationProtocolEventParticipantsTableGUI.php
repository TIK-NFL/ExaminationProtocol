<?php
declare(strict_types = 1);

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

use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolEventParticipantsTableGUI extends ilTable2GUI
{
    protected ilExaminationProtocolPlugin $plugin;
    public array $current_filter;
    private Factory $ui_factory;
    protected Renderer $renderer;
    protected array $participant_ids;

    /**
     * @param object $a_parent_obj
     * @param string $a_parent_cmd
     * @param string $a_template_context
     * @throws ilException
     */
    public function __construct(object $a_parent_obj, string $a_parent_cmd = "", string $a_template_context = "")
    {
        global $DIC;
        $ilCtrl = $DIC['ilCtrl'];
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        $this->ui_factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->setId("texa_protocol_participant");
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);

        $this->setTitle($this->plugin->txt('participant_table_title'));
        $this->setFormName('form_texa_protocol_participant');
        $this->addHiddenInput("entry_id", $_SESSION['examination_protocol']['entry_id']);
        $this->buildTable();
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
    }

    private function buildTable(){
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        $this->setShowRowsSelector(true);
        $this->setLimit(5000);
        $this->setSelectAllCheckbox('participant');
        $this->addMultiCommand("assign", $this->plugin->txt('participant_assign'));
        $this->addMultiCommand("unassign", $this->plugin->txt('participant_unassign'));
        $this->setRowTemplate('tpl.protocol_participant_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        $this->initFilter();
        $this->addColumn('', 'participant', '1px', true);
        $this->addColumn($this->plugin->txt("participant_table_column_name"), 'name');
        $this->addColumn($this->plugin->txt("participant_table_column_login"), 'login');
        $this->addColumn($this->plugin->txt("participant_table_column_mrt"), 'matriculation');
        $this->addColumn($this->plugin->txt("participant_table_column_email"), 'email');
        $this->addColumn($this->plugin->txt("participant_table_column_assigned"), 'assigned', '100');
        $this->setDefaultOrderField("matriculation");
        $this->setDefaultOrderDirection("asc");
    }

    /**
     * @throws Exception
     */
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
     *
     * @param array $a_set
     * @return void
     */
    protected function fillRow(array $a_set) : void
    {
        parent::fillRow([
            'CHECKBOX' => $a_set['participant_id'],
            'NAME' => $a_set['name'],
            'LOGIN' => $a_set['login'],
            'MATRICULATION' => $a_set['matriculation'],
            'EMAIL' => $a_set['email'],
            'CHECKED' => $a_set['glyph'],
        ]);
    }
}
