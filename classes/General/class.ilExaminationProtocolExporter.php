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

namespace ILIAS\Plugin\ExaminationProtocol;

use Exception;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Resource\StorableResource;
use ILIAS\ResourceStorage\Services;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolExporter
{
    private ilExaminationProtocolDBConnector $db;
    private Services $irss;
    private int $test_id;
    private array $properties;

    public function getTestId(): int
    {
        return $this->test_id;
    }

    public function setTestId(int $test_id): void
    {
        $this->test_id = $test_id;
    }

    public function __construct(int $test_id)
    {
        global $DIC;
        $this->irss = $DIC->resourceStorage();
        $this->db = new ilExaminationProtocolDBConnector();
        $this->test_id = intval($test_id);
        $this->properties = $this->db->getSettingByTestID($this->test_id);
    }

    /**
     * @throws Exception
     */
    public function getResource(): StorableResource
    {
        if (!$this->hasRevision()) {
            $resource_id = $this->createResource();
        } else  {
            $resource_id = $this->db->getResourceIDbyTestID($this->test_id);
            $resource_id = $resource_id['resource_storage_id'];
            $resource_id = $this->irss->manage()->find($resource_id);
        }
        return $this->irss->manage()->getResource($resource_id);
    }

    public function hasRevision(): bool
    {
        $resource_id = $this->db->getResourceIDbyTestID($this->test_id);
        if (isset($resource_id['resource_storage_id']) || is_null($resource_id['resource_storage_id']) ) {
            if (is_null($resource_id['resource_storage_id'])) {
                return false;
            }
            $resource_id = $this->irss->manage()->find($resource_id['resource_storage_id']);
            if (!isset($resource_id) || $resource_id == '') {
                return false;
            }
            $resource = $this->irss->manage()->getResource($resource_id);
            $revisions = $resource->getAllRevisions();
            if(empty($revisions)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function getLatestExportID(): ResourceIdentification
    {
        $resource = $this->getResource();
        $revision = $resource->getCurrentRevision();
        return $revision->getIdentification();
    }

    /**
     * @throws Exception
     */
    public function deleteProtocolRevisions(array $ids): void
    {
        $resource = $this->getResource();
        foreach ($resource->getAllRevisions() as $revision) {
            if (in_array($revision->getVersionNumber(), $ids)) {
                $this->irss->manage()->removeRevision($resource->getIdentification(), $revision->getVersionNumber());
            }
        }
    }

    public function createHTMLProtocol(): string
    {
        $html_builder = new ilExaminationProtocolHTMLBuilder($this->test_id);
        return $html_builder->getHTML();
    }

    /**
     * @throws Exception
     */
    public function createResource(): ?ResourceIdentification
    {
        $resource_id = $this->db->getResourceIDbyTestID($this->test_id)['resource_storage_id'];
        $html = $this->createHTMLProtocol();
        $stream = Streams::ofString($html);
        $stakeholder = new ilExaminationProtocolStakeholder();
        $filename = "examprotocol_ " . $this->test_id . "_". strtotime('now') .".html";
        if (empty($resource_id)) {
            $resource_identification = $this->irss->manage()->stream($stream, $stakeholder, $filename);
            $resource_id = $resource_identification->serialize();
        }
        else if (isset($resource_id)) {
            $resource_identification = $this->irss->manage()->find($resource_id);
            $this->irss->manage()->appendNewRevisionFromStream($resource_identification, $stream, $stakeholder, $filename);
        } else {
            $resource_identification = $this->irss->manage()->stream($stream, $stakeholder, $filename);
            $resource_id = $resource_identification->serialize();
        }
        $this->db->setResourceIDbyTestID($this->test_id, $resource_id);
        return $resource_identification;
    }
}
