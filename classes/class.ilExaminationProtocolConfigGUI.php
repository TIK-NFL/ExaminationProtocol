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

use ILIAS\DI\Container;
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolSettings;
use ILIAS\UI\Component\Input\Container\Form\Standard;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Example configuration user interface class
 * Plugins -> Actions -> Configure
 * Somehow has to be here
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @version  $Id$
 */
class ilExaminationProtocolConfigGUI extends ilPluginConfigGUI
{
    /** @var ilLanguage */
    private $lng;
    /** @var Container */
    private $dic;
    /** @var RequestInterface|ServerRequestInterface */
    private $request;
    /** @var ilCtrl */
    protected $ctrl;
    /** @var ilGlobalTemplateInterface */
    protected $pageTemplate;
    /** @var ilExaminationProtocolSettings  */
    protected $settings;
    /** @var Factory */
    private $ui_factory;
    /** @var Renderer  */
    private $renderer;

    /**
     *
     */
    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('trac');
        $this->ctrl = $DIC->ctrl();
        $this->pageTemplate = $DIC->ui()->mainTemplate();
        $this->ui_factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->request = $DIC->http()->request();
        $this->settings = $this->dic['plugin.examinationprotocol.settings'];
    }

    /**
    * Handles all commmands, default is "configure"
    */
    public function performCommand($cmd) : void
    {
        switch ($cmd) {
            case "configure":
                $this->configure();
                break;
            case "save":
                $this->save();
                break;
        }
    }

    /**
     * Configure screen
     */
    public function configure() : void
    {
        global $tpl;
        $form = $this->renderer->render($this->initConfigurationForm());
        $tpl->setContent($form);
    }

    /**
     * Init configuration form.
     * @return Standard
     */
    public function initConfigurationForm() : Standard
    {
        $rb_operation_mode = $this->ui_factory->input()->field()->radio($this->plugin_object->txt('config_radiobutton_title'))
            ->withOption('0', $this->plugin_object->txt('config_radiobutton_option_off'))
          //->withOption('1', $this->plugin_object->txt('examination_protocol_config_radiobutton_option_manual'), 'currently no effect')
            ->withOption('2', $this->plugin_object->txt('config_radiobutton_option_all'))
            ->withValue($this->settings->getOperationModeKey() ?? '0');

        $section_content = [$rb_operation_mode];
        $section = $this->ui_factory->input()->field()->section($section_content, $this->plugin_object->txt('config_section_title'));
        $form_action = $this->ctrl->getFormAction($this, 'save');
        $form = $this->ui_factory->input()->container()->form()->standard($form_action, [$section]);
        return $form;
    }
    
    /**
     * Save form input (currently does not save anything to db)
     */
    public function save() : void
    {
        $form = $this->initConfigurationForm();
        $form = $form->withRequest($this->request);
        $result = $form->getData();
        $this->settings->setOperationMode((int) $result[0][0]);

        ilUtil::sendSuccess($this->lng->txt("saved_successfully"), true);
        $this->ctrl->redirect($this, "configure");
    }
}
