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
 *********************************************************************/

namespace ILIAS\Plugin\ExaminationProtocol;

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolStakeholder extends AbstractResourceStakeholder
{
    private static ilExaminationProtocolStakeholder $instance;

    private int $owner;
    private string $plugin_id;

    public function __construct()
    {
        $this->owner = 6;
        $this->plugin_id = 'texa';
    }

    public static function getInstance(): ilExaminationProtocolStakeholder
    {
        if (!isset(self::$instance)) {
            self::$instance = new ilExaminationProtocolStakeholder();
        }
        return self::$instance;
    }

    public function getId() : string
    {
        return $this->plugin_id;
    }

    public function getOwnerOfNewResources() : int
    {
        return $this->owner;
    }
}
