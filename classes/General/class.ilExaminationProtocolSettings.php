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

use ilSetting;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolSettings
{
    /** @var ilSetting */
    private $settings;
    /** @var array  */
    private $examination_protocol_settings = [];
    /** @var string[]  */
    public const OPERATION_MODES = [
        0 => 'off',
        1 => 'manual',
        2 => 'all'
    ];

    public function __construct(ilSetting $settings)
    {
        $this->settings = $settings;
        $this->read();
    }

    public function setOperationMode(int $mode) : void
    {
        $this->examination_protocol_settings['mode'] = self::OPERATION_MODES[$mode];
        $this->save();
    }

    public function getOperationModeKey() : ?int
    {
        if (is_null($this->examination_protocol_settings['mode']))
        {
            return null;
        }
        return array_search($this->examination_protocol_settings['mode'], self::OPERATION_MODES);
    }

    public function getOperationMode() : ?string
    {
        if (is_null($this->examination_protocol_settings['mode']))
        {
            return null;
        }
        return $this->examination_protocol_settings['mode'];
    }

    public function save() : void
    {
        $this->settings->set('examination_protocol_settings', json_encode($this->examination_protocol_settings));
    }

    private function read() : void
    {
        $examination_protocol_setting = $this->settings->get('examination_protocol_settings', null);
        if ($examination_protocol_setting !== null && $examination_protocol_setting !== '') {
            $examination_protocol_setting = json_decode($examination_protocol_setting, true);
        }
        if (!is_array($examination_protocol_setting)) {
            $examination_protocol_setting = [];
        }
        $this->examination_protocol_settings = $examination_protocol_setting;
    }
}
