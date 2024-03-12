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

use ILIAS\Plugin\ExaminationProtocol\GUI\ilExaminationProtocolTableBaseController;
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolExporter;
use ILIAS\UI\Component\Legacy\Legacy;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 * @ilCtrl_isCalledBy ilExaminationProtocolExportGUI: ilObjectTestGUI, ilObjTestGUI, ilUIPluginRouterGUI, ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilExaminationProtocolEventInput
 * @ilCtrl_Calls ilExaminationProtocolExportGUI:  ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilObjTestSettingsGeneralGUI, ilExaminationProtocolEventInput
*/

class ilExaminationProtocolExportGUI extends ilExaminationProtocolTableBaseController
{
    /** @var ilExaminationProtocolExporter  */
    private $exporter;

    public function __construct()
    {
        parent::__construct();
        $this->tabs->activateSubTab(self::EXPORT_TAB_ID);
        $this->exporter = new ilExaminationProtocolExporter((string) $this->test_object->test_id);
    }

    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd()) {
            case self::CMD_CREATE_EXPORT:
                $this->createExport();
                break;
            case self::CMD_DOWNLOAD_EXPORT:
                $this->downloadExport();
                break;
        }
    }

    protected function buildGUI(): void
    {
        $this->buildToolbar();
        $this->buildNotification();
        $export_table = $this->buildTable();
        $page = [$export_table];
        $this->tpl->setContent($this->renderer->render($page));
        $this->tpl->printToStdout();
    }

    protected function buildNotification(): void
    {
        if (isset($_REQUEST['Success']) && $_REQUEST['Success'] == '1') {
            $this->tpl->setOnScreenMessage('info', $this->plugin->txt('protocol_export_created'));
        }
    }

    protected function buildTable(): Legacy
    {
        $export_table = new ilExaminationProtocolExportTableGUI($this, self::CMD_SHOW);
        $this->loadDataIntoTable($export_table);
        return $this->ui_factory->legacy($export_table->getHTML());
    }

    private function loadDataIntoTable($export_table): void
    {
        if(!$this->exporter->hasRevision()) {
            return;
        }
        $data = [];
        $resource = $this->exporter->getResource();
        foreach ($resource->getAllRevisions() as $revision){
            $this->ctrl->setParameterByClass(self::class, "resource_id", $revision->getIdentification());
            $download_url = $this->ctrl->getLinkTargetByClass(self::class, self::CMD_DOWNLOAD_EXPORT);
            $download_btn = $this->ui_factory->button()->shy($this->plugin->txt("download"), $download_url);
            $download_btn_render = $this->renderer->render($download_btn);
            $procesed_date = strval($revision->getInformation()->getCreationDate()->getTimestamp());
            $row = [
                'version_number' => $revision->getVersionNumber(),
                'file' => $revision->getTitle(),
                'size' => ($revision->getInformation()->getSize() / 1000) ." kB",
                'date' => $procesed_date,
                'resource_id' => $revision->getIdentification(),
                'action' => $download_btn_render
            ];
            $data[] = $row;
        }
        $export_table->setData($data);
    }

    protected function buildToolbar(): void
    {
        $btn_create_export = ilLinkButton::getInstance();
        $btn_create_export->setCaption($this->plugin->txt("protocol_create_export"), false);
        $btn_create_export->setUrl($this->ctrl->getLinkTargetByClass(self::class, self::CMD_CREATE_EXPORT));
        $this->toolbar->addButtonInstance($btn_create_export);
    }

    protected function createExport(): void
    {
        if (!is_null($this->exporter->createResource())) {
            $this->ctrl->setParameterByClass(self::class, "Success", true );
        }
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function deleteContent(): void
    {
        if (!is_null($_POST['version_number'])) {
            $this->exporter->deleteProtocolRevisions($_POST['version_number']);
        }
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function downloadExport(): void
    {
        $resource = $this->exporter->getResource();
        $download_consumer = $this->irss->consume()->download($resource->getIdentification());
        $download_consumer->run();
        $this->ctrl->redirectByClass(self::class, self::CMD_SHOW);
    }

    protected function saveContent()
    {
    }

    protected function applyFilter()
    {
    }

    protected function resetFilter()
    {
    }

}
