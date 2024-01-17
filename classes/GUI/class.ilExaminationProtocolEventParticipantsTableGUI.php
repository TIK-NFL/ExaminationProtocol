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

use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolDBConnector;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolEventParticipantsTableGUI extends ilTable2GUI
{
    /** @var ilExaminationProtocolPlugin */
    protected $plugin;
    /** @var array */
    public $current_filter;
    /** @var Factory */
    private $ui_factory;
    /** @var Renderer */
    protected $renderer;
    /** @return array */
    protected $participant_ids;

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
        $this->ui_factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->setId("texa_protocol_participant");
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $db_connector = new ilExaminationProtocolDBConnector();
        $this->participant_ids = array_column(
            $db_connector->getAllProtocolParticipants($_REQUEST['entry_id']),
            "participant_id"
        );

        // Build Table
        // title
        $this->setTitle($this->plugin->txt('participant_table_title'));
        // default no entries set
        $this->setNoEntriesText($this->plugin->txt('table_empty'));
        $this->setEnableHeader(true);
        // selector
        $this->setShowRowsSelector(true);
        $this->setSelectAllCheckbox('participant');
        $this->addHiddenInput("entry_id", $_REQUEST['entry_id']);
        $this->addMultiCommand("assign", $this->plugin->txt('participant_assign'));
        $this->addMultiCommand("unassign", $this->plugin->txt('participant_unassign'));
        // row template
        $this->setRowTemplate('tpl.protocol_participant_table_row.html', ilExaminationProtocolPlugin::getInstance()->getDirectory());
        // filter
        $this->initFilter();

        // unsure
        $this->enable('sort');
        $this->enable('header');
        $this->enable('numinfo');
        $this->enable('select_all');
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
        $this->setFormName('form_texa_protocol_participant');

        // build Table
        $this->addColumn('', 'participant', '1px', true);
        $this->addColumn($this->plugin->txt("participant_table_column_name"), 'name');
        $this->addColumn($this->plugin->txt("participant_table_column_login"), 'login');
        $this->addColumn($this->plugin->txt("participant_table_column_mrt"), 'matriculation');
        $this->addColumn($this->plugin->txt("participant_table_column_email"), 'email');
        $this->addColumn($this->plugin->txt("participant_table_column_assigned"), 'assigned', 100);
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
        parent::fillRow([
            'CHECKBOX' => ilUtil::formCheckbox(false, 'participants[]', $a_set['participant_id']),
            'NAME' => $a_set['name'],
            'LOGIN' => $a_set['login'],
            'MATRICULATION' => $a_set['matriculation'],
            'EMAIL' => $a_set['email'],
            'CHECKED' => $a_set['glyph']
        ]);
    }
}
