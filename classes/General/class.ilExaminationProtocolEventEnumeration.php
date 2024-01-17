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

use ilExaminationProtocolPlugin;

/**
 * I still dont like the solution since there are only indexes stored in the database and the mapping might break by a
 * change of order in the array.
 * @author Ulf Bischoff ulf.bischoff@tik.uni-stuttgart.de
 */
class ilExaminationProtocolEventEnumeration
{
    const GENERAL = 'entry_dropdown_event_general';
    const QUESTION = 'entry_dropdown_event_question';
    const MATERIAL = 'entry_dropdown_event_material';
    const TOILET = 'entry_dropdown_event_toilet';
    const ILLNESS = 'entry_dropdown_event_illness';
    const TECHNICAL = 'entry_dropdown_event_technical';
    const OTHER = 'entry_dropdown_event_other';

    public static function getAllOptions() : array {
        return [
            self::GENERAL,
            self::QUESTION,
            self::MATERIAL,
            self::TOILET,
            self::ILLNESS,
            self::TECHNICAL,
            self::OTHER
        ];
    }

    /**
     * @param $plugin ilExaminationProtocolPlugin the plugin for easy access to the language module
     * @return array of strings containing the event options in the correct interface langauge.
     */
    public static function getAllOptionsInLanguage(ilExaminationProtocolPlugin $plugin) : array
    {
        return [
            $plugin->txt(self::GENERAL),
            $plugin->txt(self::QUESTION),
            $plugin->txt(self::MATERIAL),
            $plugin->txt(self::TOILET),
            $plugin->txt(self::ILLNESS),
            $plugin->txt(self::TECHNICAL),
            $plugin->txt(self::OTHER)
        ];
    }
}
