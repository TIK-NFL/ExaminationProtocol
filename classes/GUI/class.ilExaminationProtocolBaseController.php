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

namespace ILIAS\Plugin\ExaminationProtocol\GUI;

use ilCtrl;
use ilDatabaseException;
use ilExaminationProtocolPlugin;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolDBConnector;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ilLocatorGUI;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjTest;
use ilTabsGUI;
use ilToolbarGUI;
use ilUtil;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @version  $Id$
 */
abstract class ilExaminationProtocolBaseController
{
    /** @var string  */
    protected const CMD_SHOW = "show";
    /** @var string  */
    protected const CMD_SAVE = "save";
    /** @var string  */
    protected const CMD_DELETE = "delete";
    /** @var string  */
    protected const CMD_EXPORT = "export";
    /** @var string  */
    protected const CMD_DELETE_ALL = "delete_all";
    /** @var string  */
    protected const CMD_CONFIRMATION = "confirmation";
    /** @var string  */
    protected const CMD_ADD_PARTICIPANTS = "add_participants";
    /** @var string  */
    protected const CMD_APPLY_FILTER = "apply_filter";
    /** @var string  */
    protected const CMD_RESET_FILTER = "reset_filter";
    /** @var string  */
    protected const CMD_NEXT = "next";
    /** @var string  */
    protected const CMD_CANCEL = "cancel";

    /** @var string  */
    protected const PROTOCOL_TAB_ID = "examination_protocol_protocol";
    /** @var string  */
    protected const GENERAL_SETTINGS_TAB_ID = "examination_protocol_setting";
    /** @var string  */
    protected const SUPERVISOR_TAB_ID = "examination_protocol_supervisor";
    /** @var string  */
    protected const LOCATION_TAB_ID = "examination_protocol_location";
    /** @var string  */
    protected const PARTICIPANT_TAB_ID = "examination_protocol_participant";
    /** @var string */
    protected const EXPORT_TAB_ID = "examination_protocol_export";
    /** @var string  */
    protected const PROTOCOL_INPUT_TAB_ID = "examination_protocol_input";
    /** @var string  */
    protected const PROTOCOL_PARTICIPANT_TAB_ID = "examination_protocol_participant";

    /** @var ilLocatorGUI $ilLocator */
    private $ilLocator;
    /** @var bool  */
    private $creation_mode;
    /** @var Container|mixed */
    private $dic;
    /** @var ilGlobalTemplateInterface $tpl */
    protected $tpl;
    /** @var mixed */
    protected $lng;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var RequestInterface|ServerRequestInterface  */
    protected $request;
    /** @var ilExaminationProtocolPlugin */
    protected $plugin;
    /** @var Factory  */
    protected $ui_factory;
    /** @var \ILIAS\UI\Component\Input\Field\Factory  */
    protected $field_factory;
    /** @var Renderer  */
    protected $renderer;
    /** @var ilExaminationProtocolDBConnector */
    protected $db_connector;
    /** @var ilTabsGUI */
    protected $tabs;
    /** @var ilToolbarGUI  */
    protected $toolbar;
    /** @var array  */
    protected $settings;
    /** @var ilObjTest $test_object */
    protected $test_object = null;
    /** @var array  */
    protected $event_options;
    /** @var string */
    protected $protocol_id;
    /** @var boolean */
    protected $protocol_has_entries;

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->ctrl = $DIC['ilCtrl'];
        $this->plugin = ilExaminationProtocolPlugin::getInstance();
        // access check for all GUI pages
        if (!$this->plugin->hasAccess()) {
            $this->ctrl->returnToParent($this);
        }
        $this->ilLocator = $DIC['ilLocator'];
        $this->request = $DIC->http()->request();
        $this->test_object = ilObjectFactory::getInstanceByRefId($_GET['ref_id']);

        $this->tpl = $DIC['tpl'];
        $this->lng = $DIC['lng'];
        $this->ui_factory = $DIC->ui()->factory();
        $this->field_factory = $this->ui_factory->input()->field();
        $this->renderer = $DIC->ui()->renderer();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC['ilToolbar'];

        // unified event options TODO Refactor to constant
        $this->event_options = [
            $this->plugin->txt("entry_dropdown_event_general"),
            $this->plugin->txt("entry_dropdown_event_question"),
            $this->plugin->txt("entry_dropdown_event_material"),
            $this->plugin->txt("entry_dropdown_event_toilet"),
            $this->plugin->txt("entry_dropdown_event_illness"),
            $this->plugin->txt("entry_dropdown_event_technical"),
            $this->plugin->txt("entry_dropdown_event_other"),
        ];

        // Data base
        $this->db_connector = new ilExaminationProtocolDBConnector();
        $this->protocol_id = $this->db_connector->getProtocolIDByTestID($this->test_object->test_id);
        $this->settings = $this->db_connector->getSettingByTestID($this->test_object->test_id);
        $this->protocol_has_entries = !empty($this->db_connector->getAllProtocolEntriesByProtocolID($this->protocol_id));
        // create a general settings entry for the protocol ID if not already existing
        if (is_null($this->protocol_id)) {
            $this->db_connector->createEmptySetting([['integer', $this->test_object->test_id]]);
        }

        $this->setTemplateDefaults();
    }

    private function setTemplateDefaults() : void
    {
        $this->tpl->loadStandardTemplate();
        $this->tpl->setLocator();
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_tst.svg'));
        $this->tpl->setTitle($this->test_object->getTitle());
        $this->tpl->setDescription($this->test_object->getDescription());
        $this->ilLocator->addRepositoryItems($this->test_object->getRefId());
        $this->ilLocator->addItem($this->test_object->getTitle(), $this->ctrl->getLinkTargetByClass('ilobjtestgui'));
        $this->ctrl->setParameterByClass('ilobjtestgui', 'ref_id', $this->test_object->getRefId());
    }

    public function getProtocolId() : ?string
    {
        return $this->protocol_id;
    }

    /**
     * @param boolean $a_mode default true
     */
    public function setCreationMode(bool $a_mode = true) : void
    {
        $this->creation_mode = $a_mode;
    }

    public function executeCommand() : void
    {
    }
}
