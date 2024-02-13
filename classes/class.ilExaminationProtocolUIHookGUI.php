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
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolSettings;

/**
 * User interface hook class
 *
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
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
        'ilrepositorygui',
        'ilrepositorysearchgui',
        '',
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
        'iltestparticipantstablegui',
        'ilmdeditorgui',
        'iltestexportgui',
        'ilpermissiongui',
        'ilobjectpermissionstatusgui',
        'ilpermissiongui',
        'ilinfoscreengui',
        'ilpropertyformgui',
    ];

    /** @var string[] */
    private const FORBIDDEN_CMDS = [
        'detailedevaluation',
        'outuserpassdetails',
        'showquestion',
    ];

    private Container $dic;
    protected ilCtrl $ctrl;
    protected ?ilTabsGUI $ilTabs;

    public function __construct()
    {
        global $DIC, $ilTabs;
        $this->dic = $DIC;
        $this->ctrl = $DIC['ilCtrl'];
        $this->ilTabs = $ilTabs;
    }

    public function modifyGUI(string $a_comp, string $a_part, $a_par = array()): void
    {
        if ($a_part == 'tabs'
            && $this->ctrl->getContextObjType() == 'tst'
            && in_array(strtolower($_REQUEST['baseClass'] ?? ''), self::ALLOWED_BASE_CLASSES)
            && in_array(strtolower($this->ctrl->getCmdClass()), self::ALLOWED_CMD_CLASSES)
            && !in_array(strtolower($this->ctrl->getCmd()), self::FORBIDDEN_CMDS))
        {
            if (!$this->plugin_object->hasAccess()) {
                return;
            }
            $pluginSettings = $this->dic['plugin.examinationprotocol.settings'];
            if ($pluginSettings->getOperationMode() != ilExaminationProtocolSettings::OPERATION_MODES['2']) {
                return;
            }
            $this->ctrl->saveParameterByClass(ilUIPluginRouterGUI::class, 'ref_id');
            $this->ilTabs->addTab(
                'examination_protocol',
                $this->plugin_object->txt('protocol_tab_name'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[0]], 'show')
            );
            $examination_entry = [end($this->ilTabs->target)];
            array_pop($this->ilTabs->target);
            array_splice($this->ilTabs->target, 3, 0, $examination_entry);
            $_SESSION['examination_protocol']['tab_target'] = $this->ilTabs->target;
        } elseif (
            $a_part == 'sub_tabs'
            && in_array(strtolower($this->ctrl->getCmdClass()), self::SUBTABS))
        {
            $this->ilTabs->addSubTab(
                'examination_protocol_protocol',
                $this->plugin_object->txt('sub_tab_protocol'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[0]], 'show')
            );
            $this->ilTabs->addSubTab(
                'examination_protocol_setting',
                $this->plugin_object->txt('sub_tab_settings'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[1]], 'show')
            );
            $this->ilTabs->addSubTab(
                'examination_protocol_supervisor',
                $this->plugin_object->txt('sub_tab_supervisors'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[2]], 'show')
            );
            $this->ilTabs->addSubTab(
                'examination_protocol_location',
                $this->plugin_object->txt('sub_tab_locations'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[3]], 'show')
            );
            $this->ilTabs->addSubTab(
                'examination_protocol_participant',
                $this->plugin_object->txt('sub_tab_participants'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[4]], 'show')
            );
            $this->ilTabs->addSubTab(
                'examination_protocol_export',
                $this->plugin_object->txt('sub_tab_export'),
                $this->ctrl->getLinkTargetByClass([ilUIPluginRouterGUI::class, self::SUBTABS[5]], 'show')
            );
            $_SESSION['examination_protocol']['tab_sub_target'] = $this->ilTabs->sub_target;
        }

        if ($a_part == 'tabs'
            && $this->ctrl->getCmdClass() == 'ilRepositorySearchGUI'
            && isset($_SESSION['examination_protocol']['tab_target'])
            && isset($this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-1])
            && isset($this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-2])
            && $this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-1]['cmdClass'] == 'ilRepositorySearchGUI'
            && $this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-2]['cmdClass'] == 'ilExaminationProtocolParticipantsGUI'
            && !in_array(strtolower($_REQUEST['cmdClass']), self::FORBIDDEN_CMDS))
        {
            $this->ilTabs->target = $_SESSION['examination_protocol']['tab_target'];
            $this->ilTabs->activateTab('examination_protocol');
        }

        if ($a_part == 'sub_tabs'
            && (in_array(strtolower($this->ctrl->getCmdClass()), self::SUBTABS)
                || (
                    isset($this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-1])
                    && $this->ctrl->getCallHistory()[count($this->ctrl->getCallHistory())-1]['cmdClass'] == 'ilExaminationProtocolParticipantsGUI'
                ))
            && !in_array(strtolower($_REQUEST['cmdClass']), self::FORBIDDEN_CMDS))
        {
            if (isset($_SESSION['examination_protocol']['tab_target'])) {
                $this->ilTabs->target = $_SESSION['examination_protocol']['tab_target'];
            }
            if (isset($_SESSION['examination_protocol']['tab_sub_target']) && !in_array($this->ctrl->getCmdClass(), self::INPUTTABS)) {
                $this->ilTabs->sub_target = $_SESSION['examination_protocol']['tab_sub_target'];
            } elseif (in_array($this->ctrl->getCmdClass(), self::INPUTTABS)) {
                $this->ilTabs->sub_target = [];
            }
            $this->ilTabs->activateTab('examination_protocol');
            switch ($this->ctrl->getCmdClass()) {
                case 'ilexaminationprotocoleventgui':
                    $this->ilTabs->activateSubTab('examination_protocol_protocol');
                    break;
                case 'ilexaminationprotocolgeneralsettingsgui':
                    $this->ilTabs->activateSubTab('examination_protocol_setting');
                    break;
                case 'ilexaminationprotocollocationgui':
                    $this->ilTabs->activateSubTab('examination_protocol_location');
                    break;
                case 'ilexaminationprotocolparticipantsgui':
                    $this->ilTabs->activateSubTab('examination_protocol_participant');
                    break;
                case 'ilexaminationprotocolsupervisorgui':
                    $this->ilTabs->activateSubTab('examination_protocol_supervisor');
                    break;
                case 'ilexaminationprotocolexportgui':
                    $this->ilTabs->activateSubTab('examination_protocol_export');
                    break;
            }
        }
    }
}
