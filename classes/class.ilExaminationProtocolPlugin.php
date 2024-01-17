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
    // plugin definitions
    public const CTYPE = IL_COMP_SERVICE;
    public const CNAME = "UIComponent";
    public const SLOT_ID = "uihk";
    public const PNAME = "ExaminationProtocol";
    public const ID = "texa";

    /** @var self */
    private static $instance = null;
    /** @var bool */
    protected static $initialized = false;
    /** @var Container */
    protected $dic;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        parent::__construct();
    }

    public function getPluginName() : string
    {
        return self::PNAME;
    }

    /**
     * @param string $test_id id of the ILIAS test Object a protocol possibly could be created.
     * @return ResourceIdentification the IRSS id of the protocols HTML file. the protocol might be empty if no protocol information is available
     */
    public function getProtocolExportIDByTestID(string $test_id) : ResourceIdentification
    {
        $exporter = new ilExaminationProtocolExporter($test_id);
        return $exporter->getLatestExportID();
    }

    protected function init() : void
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

    public function registerAutoloader() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * @return self
     */
    public static function getInstance() : ?ilExaminationProtocolPlugin
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        self::$instance = ilPluginAdmin::getPluginObject(
            self::CTYPE,
            self::CNAME,
            self::SLOT_ID,
            self::PNAME
        );

        return self::$instance;
    }

    public function hasAccess() : bool
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
