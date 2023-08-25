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
 * If this is not the case or you just want to try IL IAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 ********************************************************************
 */

use ILIAS\DI\Container;

/**
 * User interface hook class
 *
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @version  $Id$
 * @ingroup ServicesUIComponent
 */
class ilExaminationProtocolUIHookGUI extends ilUIHookPluginGUI
{
    /** @var string[]  */
    private const SUBTABS = [
        'ilexaminationprotocoleventgui',
        'ilexaminationprotocolgeneralsettingsgui',
        'ilexaminationprotocolsupervisorgui',
        'ilexaminationprotocollocationgui',
        'ilexaminationprotocolparticipantsgui',
        'ilexaminationprotocolexportgui',
        'ilexaminationprotocoleventinputgui',
        'ilexaminationprotocoleventparticipantsgui'
    ];

    /** @var string[]  */
    private const INPUTTABS = [
        'ilexaminationprotocoleventinputgui',
        'ilexaminationprotocoleventparticipantsgui'
    ];

    /** @var string[]  */
    private const ALLOWED_BASE_CLASSES = [
        'ilobjtestgui',
        'ilrepositorygui'
    ];

    /** @var string[]  */
    private const ALLOWED_CMD_CLASSES = [
        'ilobjtestgui',
        'iltestparticipantsgui',
        'ilobjtestsettingsgeneralgui',
        'ilparticipantstestresultsgui',
        'ilmarkschemagui',
        'ilobjtestsettingsscoringresultsgui',
        'iltestscoringbyquestionsgui',
        'iltestscoringgui',
        'iltestcorrectionsgui',
        'iltestevaluationgui',
        'ilmdeditorgui',
        'iltestexportgui',
        'ilpermissiongui',
        'ilobjectpermissionstatusgui',
        'ilpermissiongui',
    ];

    /** @var Container */
    private $dic;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var ilTabsGUI $ilTabs */
    protected $ilTabs;

    /**
     *
     */
    public function __construct()
    {
        global $DIC, $ilTabs;
        $this->dic = $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->ilTabs = $ilTabs;
    }

    /**
     *
     * Modify GUI objects, before they generate output
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par  array of parameters (depend on $a_comp and $a_part)
     * @throws ilCtrlException
     */
    public function modifyGUI($a_comp, $a_part, $a_par = array())  : void
    {
        // test for object type
        if ($a_part == "tabs"
            && $this->ctrl->getContextObjType() == "tst"
            && isset($_REQUEST['baseClass'])
            && isset($_REQUEST['cmdClass'])
            && in_array(strtolower($_REQUEST['baseClass']), self::ALLOWED_BASE_CLASSES)
            && in_array(strtolower($_REQUEST['cmdClass']), self::ALLOWED_CMD_CLASSES)) {
            // access check
            if (!$this->plugin_object->hasAccess()) {
                return;
            }

            $pluginSettings = $this->dic['plugin.examinationprotocol.settings'];
            if ($pluginSettings->getOperationModeKey() != 2) {
                return;
            }

            // Add a main protocol
            $this->ilTabs->addTab(
                "examination_protocol", //ID
                $this->plugin_object->txt("examination_protocol_tab_name"),  // text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[0]], "show") // Link
            );

            $examination_entry = [end($this->ilTabs->target)];
            array_pop($this->ilTabs->target);
            array_splice($this->ilTabs->target, 3, 0, $examination_entry);
            $_SESSION['examination_protocol']['tab_target'] = $this->ilTabs->target;
        } elseif ($a_part == "sub_tabs" && in_array($this->ctrl->getCmdClass(), self::SUBTABS)) {
            // protocol
            $this->ilTabs->addSubTab(
                "examination_protocol_protocol", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_protocol"), //text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[0]], "show") // link
            );

            // general settings
            $this->ilTabs->addSubTab(
                "examination_protocol_setting", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_settings"), //text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[1]], "show") // link
            );

            // supervisor
            $this->ilTabs->addSubTab(
                "examination_protocol_supervisor", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_supervisors"), //text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[2]], "show") // link
            );

            // location
            $this->ilTabs->addSubTab(
                "examination_protocol_location", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_locations"), //text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[3]], "show") // link
            );

            // participants
            $this->ilTabs->addSubTab(
                "examination_protocol_participant", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_participants"), //text
                $this->ctrl->getLinkTargetByClass([ilRepositoryGUI::class, self::SUBTABS[4]], "show") // link
            );

            // pdf Export
            /*$this->ilTabs->addSubTab(
                "examination_protocol_export", // ID
                $this->plugin_object->txt("examination_protocol_sub_tab_export"), //text
                $this->ctrl->getLinkTargetByClass([ilObjTestGUI::class, ilExaminationProtocolParticipantsGUI::class], "show") // link
            );*/

            // save sub target
            $_SESSION['examination_protocol']['tab_sub_target'] = $this->ilTabs->sub_target;
        }
        // add tabs
        if (($a_part == "sub_tabs" && in_array($this->ctrl->getCmdClass(), self::SUBTABS)
            || $a_part == "sub_tabs" && $this->ctrl->getCallHistory()[1]['class'] == 'ilExaminationProtocolParticipantsGUI')) {
            //reuse the tabs that were saved from the GUI modification
            if (isset($_SESSION['examination_protocol']['tab_target'])) {
                $this->ilTabs->target = $_SESSION['examination_protocol']['tab_target'];
            }
            if (isset($_SESSION['examination_protocol']['tab_sub_target']) && !in_array($this->ctrl->getCmdClass(), self::INPUTTABS)) {
                $this->ilTabs->sub_target = $_SESSION['examination_protocol']['tab_sub_target'];
            } elseif (in_array($this->ctrl->getCmdClass(), self::INPUTTABS)) {
                $this->ilTabs->sub_target = [];
            }
            // this works because the tabs are rendered after the sub tabs
            $this->ilTabs->activateTab('examination_protocol');
            switch ($this->ctrl->getCmdClass()) {
                case 'ilexaminationprotocoleventgui':
                    // protocol
                    $this->ilTabs->activateSubTab('examination_protocol_protocol');
                    break;
                case 'ilexaminationprotocolgeneralsettingsgui':
                    // setting
                    $this->ilTabs->activateSubTab('examination_protocol_setting');
                    break;
                case 'ilexaminationprotocollocationgui':
                    // location
                    $this->ilTabs->activateSubTab('examination_protocol_location');
                    break;
                case 'ilexaminationprotocolparticipantsgui':
                    // participant
                    $this->ilTabs->activateSubTab('examination_protocol_participant');
                    break;
                case 'ilexaminationprotocolsupervisorgui':
                    // supervisor
                    $this->ilTabs->activateSubTab('examination_protocol_supervisor');
                    break;
            }
        }
    }
}
