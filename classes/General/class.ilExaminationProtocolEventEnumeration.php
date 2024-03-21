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
 * TODO refactor into enumeration with PHP 8.1
 * I still dont like the solution since there are only indexes stored in the database and the mapping might break by a
 * change of order in the array.
 * @author Ulf Bischoff ulf.bischoff@tik.uni-stuttgart.de
 */
class ilExaminationProtocolEventEnumeration
{
    private const OPTIONS = [
        0 => 'entry_dropdown_event_general',
        1 => 'entry_dropdown_event_question',
        2 => 'entry_dropdown_event_material',
        3 => 'entry_dropdown_event_toilet',
        4 => 'entry_dropdown_event_illness',
        5 => 'entry_dropdown_event_technical',
        6 => 'entry_dropdown_event_other'
    ];

    /**
     * @return array of strings containing the event options in the correct interface langauge.
     */
    public static function getAllOptionsInLanguage(): array
    {
        $plugin = ilExaminationProtocolPlugin::getInstance();
        return [
            0 => $plugin->txt(self::OPTIONS[0]),
            1 => $plugin->txt(self::OPTIONS[1]),
            2 => $plugin->txt(self::OPTIONS[2]),
            3 => $plugin->txt(self::OPTIONS[3]),
            4 => $plugin->txt(self::OPTIONS[4]),
            5 => $plugin->txt(self::OPTIONS[5]),
            6 => $plugin->txt(self::OPTIONS[6])
        ];
    }

    /**
     * @param int $key Usually stored in the Database per Protocol entry
     * @return string The correct localized languagestring for the key
     */
    public static function getLanguageStringForKey(int $key): string
    {
        $plugin = ilExaminationProtocolPlugin::getInstance();
        return $plugin->txt(self::OPTIONS[$key]);
    }
}
