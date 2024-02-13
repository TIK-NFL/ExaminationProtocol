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
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolExporter;
use ILIAS\Plugin\ExaminationProtocol\ilExaminationProtocolSettings;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolPlugin extends ilUserInterfaceHookPlugin
{
    public const CTYPE = 'Services';
    public const CNAME = 'UIComponent';
    public const SLOT_ID = 'uihk';
    public const PNAME = 'ExaminationProtocol';
    public const ID = 'texa';

    private static ?ilExaminationProtocolPlugin $instance = null;
    protected static bool $initialized = false;
    protected Container $dic;

    public function __construct()
    {
        global $DIC;
        global $ilDB;
        $this->dic = $DIC;
        $cr = $DIC['component.repository'];
        parent::__construct($ilDB, $cr, ilExaminationProtocolPlugin::ID);
    }

    public function getPluginName(): string
    {
        return self::PNAME;
    }

    /**
     * @param string $test_id id of the ILIAS test Object a protocol possibly could be created.
     * @return ResourceIdentification the IRSS id of the protocols HTML file. the protocol might be empty if no protocol information is available
     */
    public function getProtocolExportByTestID(string $test_id): ResourceIdentification
    {
        $exporter = new ilExaminationProtocolExporter(intval($test_id));
        return $exporter->getLatestExportID();
    }



    protected function init(): void
    {
        parent::init();
        $this->registerAutoloader();
        if (!self::$initialized) {
            self::$initialized = true;
            $this->dic['plugin.examinationprotocol.settings'] = function (Container $c) : ilExaminationProtocolSettings {
                return new ilExaminationProtocolSettings(
                    new ilSetting($this->getId())
                );
            };
        }
    }

    public function registerAutoloader(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * @return ilExaminationProtocolPlugin|null
     */
    public static function getInstance(): ?ilExaminationProtocolPlugin
    {
        if (null === self::$instance) {
            global $DIC;
            $cf = $DIC['component.factory'];
            self::$instance = $cf->getPlugin(ilExaminationProtocolPlugin::ID);
        }
        return self::$instance;
    }

    public function hasAccess(): bool
    {
        /** @var $ilAccess ilAccessHandler */
        global $ilAccess;
        if (!isset($_GET['ref_id']) || !is_numeric($_GET['ref_id'])) {
            return false;
        }
        if ('tst' != ilObject::_lookupType(ilObject::_lookupObjId((int) $_GET['ref_id']))) {
            return false;
        }
        return $ilAccess->checkAccess('write', '', (int) $_GET['ref_id']);
    }
}
