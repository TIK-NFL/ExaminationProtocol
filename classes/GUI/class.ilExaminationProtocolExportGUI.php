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

use ILIAS\Plugin\ExaminationProtocol\GUI\ilExaminationProtocolBaseController;
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolExporter;
use ILIAS\UI\Component\Legacy\Legacy;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolExportGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventInput
 * @ilCtrl_Calls ilExaminationProtocolExportGUI:  ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventInput
*/

class ilExaminationProtocolExportGUI extends ilExaminationProtocolBaseController
{
    /** @var string */
    private $html;
    /** @var ilExaminationProtocolExporter  */
    private $exporter;

    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::EXPORT_TAB_ID);
        $this->exporter = new ilExaminationProtocolExporter((string) $this->test_object->test_id);
    }

    public function executeCommand() : void
    {
        switch ($this->ctrl->getCmd()) {
            default:
            case self::CMD_SHOW:
                $this->buildGUI();
                break;
            case self::CMD_CREATE_EXPORT:
                $this->createExport();
                $this->buildGUI();
                break;
            case self::CMD_DOWNLOAD_EXPORT:
                $this->downloadExport();
                $this->buildGUI();
                break;
            case self::CMD_DELETE:
                $this->deleteExports();
                $this->buildGUI();
                break;
        }
    }

    private function buildGUI() : void
    {
        $this->buildToolbar();
        $export_table = $this->buildTable();
        $page = [$export_table];
        $this->tpl->setContent($this->renderer->render($page));
        $this->tpl->printToStdout();
    }

    protected function buildTable() : Legacy
    {
        $export_table = new ilExaminationProtocolExportTableGUI($this, self::CMD_SHOW);
        $this->loadDataIntoTable($export_table);
        return $this->ui_factory->legacy($export_table->getHTML());
    }

    private function loadDataIntoTable($export_table) : void
    {
        if(!$this->exporter->hasRevision()){
            return;
        }
        $data = [];
        $resource = $this->exporter->getResource();
        foreach ($resource->getAllRevisions() as $revision){
            $this->ctrl->setParameterByClass(ilExaminationProtocolExportGUI::class, "resource_id", $revision->getIdentification());
            $download_url = $this->ctrl->getLinkTargetByClass(ilExaminationProtocolExportGUI::class, self::CMD_DOWNLOAD_EXPORT);
            $download_btn = $this->ui_factory->button()->shy($this->plugin->txt("download"), $download_url);
            $download_btn_render = $this->renderer->render($download_btn);
            $row = [
                'version_number' => $revision->getVersionNumber(),
                'file' => $revision->getTitle(),
                'size' => ($revision->getInformation()->getSize() / 1000) ." kB",
                'date' => $revision->getInformation()->getCreationDate()->format('d.m.Y H:i'),
                'resource_id' => $revision->getIdentification(),
                'action' => $download_btn_render
            ];
            $data[] = $row;
        }
        $export_table->setData($data);
    }

    protected function buildToolbar() : void
    {
        $btn_create_export = ilLinkButton::getInstance();
        $btn_create_export->setCaption($this->plugin->txt("protocol_create_export"), false);
        $btn_create_export->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_CREATE_EXPORT));
        $this->toolbar->addButtonInstance($btn_create_export);
    }

    public function createExport() : void
    {
        $this->exporter->createResource();
        $this->tpl->setOnScreenMessage('info', $this->plugin->txt('protocol_export_created'));
    }

    public function deleteExports() : void
    {
        if (!is_null($_POST['version_number'])){
            $this->exporter->deleteProtocolRevisions($_POST['version_number']);
        }
    }

    public function downloadExport(): void
    {
        $resource = $this->exporter->getResource();
        $download_consumer = $this->irss->consume()->download($resource->getIdentification());
        $download_consumer->run();
    }

    public function getHTML() : string
    {
        return $this->html;
    }
}
