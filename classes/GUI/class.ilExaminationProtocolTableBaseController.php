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


/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
abstract class ilExaminationProtocolTableBaseController extends ilExaminationProtocolBaseController
{
    /** @var string  */
    protected const CMD_APPLY_FILTER = "apply_filter";
    /** @var string  */
    protected const CMD_RESET_FILTER = "reset_filter";
    /** @var ilTable2GUI */
    protected $table;

    public function __construct()
    {
        parent::__construct();
    }

    public function executeCommand(): void
    {
        parent::executeCommand();
        switch ($this->ctrl->getCmd())
        {
            case self::CMD_APPLY_FILTER:
                $this->applyFilter();
                break;
            case self::CMD_RESET_FILTER:
                $this->resetFilter();
                break;
        }
    }

    function textSorter( $field)
    {
        return function ($a, $b) use ($field)
        {
            return strcmp($a[$field], $b[$field]);
        };
    }

    abstract protected function applyFilter();

    abstract protected function resetFilter();

}
