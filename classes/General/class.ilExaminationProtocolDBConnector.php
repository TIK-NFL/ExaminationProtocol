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

use ilDBInterface;

/**
 * @author Ulf Bischoff <ulf.bischoff@tik.uni-stuttgart.de>
 */
class ilExaminationProtocolDBConnector
{
    /** @var string */
    public const  SETTINGS_PRIMARY_KEY = 'protocol_id';

    /** @var string  */
    public const TEST_ID_KEY = 'test_id';
    /** @var string  */
    public const SETTINGS_TABLE_NAME = 'tst_uihk_texa_general';
    /** @var array */
    public const SETTINGS_TABLE_FIELDS = ['test_id', 'protocol_title', 'protocol_desc', 'type_exam', 'type_only_ilias',
                                          'type_desc', 'supervision', 'exam_policy', 'exam_policy_desc', 'location',
                                          'resource_storage_id'];

    /** @var string  */
    public const LOCATION_TABLE_NAME = 'tst_uihk_texa_location';
    /** @var string */
    public const LOCATION_PRIMARY_KEY = 'location_id';
    /** @var array */
    public const LOCATION_TABLE_FIELDS = ['protocol_id', 'location'];

    /** @var string  */
    public const SUPERVISOR_TABLE_NAME = 'tst_uihk_texa_supvis';
    /** @var string */
    public const SUPERVISOR_PRIMARY_KEY = 'supervisor_id';
    /** @var array */
    public const SUPERVISOR_TABLE_FIELDS = ['protocol_id', 'name'];

    /** @var string  */
    public const PROTOCOL_TABLE_NAME = 'tst_uihk_texa_protocol';
    /** @var string */
    public const PROTOCOL_PRIMARY_KEY = 'entry_id';
    /** @var array */
    public const PROTOCOL_TABLE_FIELDS = ['protocol_id', 'supervisor_id', 'location_id', 'start', 'end', 'creation',
                                          'event', 'comment', 'last_edit', 'last_edited_by', 'created_by'];

    /** @var string  */
    public const PROTOCOL_PARTICIPANT_TABLE_NAME = 'tst_uihk_texa_propar';
    /** @var string */
    public const PROTOCOL_PARTICIPANT_PRIMARY_KEY = 'propar_id';
    /** @var array */
    public const PROTOCOL_PARTICIPANT_FIELDS = ['protocol_id', 'entry_id', 'participant_id'];

    /** @var string  */
    public const PARTICIPANTS_TABLE_NAME = 'tst_uihk_texa_partic';
    /** @var string */
    public const PARTICIPANTS_PRIMARY_KEY = 'participant_id';
    /** @var array */
    public const PARTICIPANTS_TABLE_FIELDS = ['protocol_id', 'usr_id'];

    private ilDBInterface $ilDB;

    public function __construct()
    {
        global $ilDB;
        $this->ilDB = $ilDB;
    }

    public function insertSetting(array $values) : void
    {
        // add auto increment to first array element
        $primaryKey = [self::SETTINGS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SETTINGS_TABLE_NAME)]];
        $keyValue = array_combine(self::SETTINGS_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::SETTINGS_TABLE_NAME, $primaryKey + $keyValue);
    }

    public function createEmptySetting(array $values) : void
    {
        // add auto increment to first array element
        $primaryKey = [self::SETTINGS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SETTINGS_TABLE_NAME)]];
        $keyValue = array_combine([self::SETTINGS_TABLE_FIELDS[0]], $values);
        $this->ilDB->insert(self::SETTINGS_TABLE_NAME, $primaryKey + $keyValue);
    }

    public function updateSetting(array $values, array $where) : void
    {
        $columns = array_combine(self::SETTINGS_TABLE_FIELDS, $values);
        $this->ilDB->update(self::SETTINGS_TABLE_NAME, $columns, $where);
    }

    public function getSettingByTestID(int $test_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );
        return (array) $this->ilDB->fetchObject($query);
    }

    public function getResourceIDbyTestID(int $test_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT resource_storage_id FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );

        return (array) $this->ilDB->fetchObject($query);
    }

    public function setResourceIDbyTestID(int $test_id, string $resource_id) : void
    {
        $query = "UPDATE " . self::SETTINGS_TABLE_NAME . " SET resource_storage_id = '".$resource_id."' WHERE " . self::TEST_ID_KEY . "= ".$test_id.";";
        $this->ilDB->manipulate($query);
    }

    public function settingsExistByTestID(int $test_id) : bool
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . "= %s",
            array('integer'),
            array($test_id)
        );
        $obj = $this->ilDB->fetchObject($query);
        if (empty($obj)) {
            return false;
        }
        return true;
    }

    public function getProtocolIDByTestID(int $test_id) : ?string
    {
        $query = $this->ilDB->queryF(
            "SELECT protocol_id FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );
        $result = (array) $this->ilDB->fetchObject($query);
        if (isset($result['protocol_id'])) {
            return $result['protocol_id'];
        }
        return null;
    }

    public function getTestTitleById(int $test_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT od.title FROM tst_tests AS tt, object_data AS od WHERE tt.test_id = %s AND tt.obj_fi = od.obj_id",
            array('integer'),
            array($test_id)
        );
        return (array) $this->ilDB->fetchObject($query);
    }

    public function getAllSupervisorsByProtocolID(int $protocol_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getSupervisorBySupervisorID(int $supervisor_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT name FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SUPERVISOR_PRIMARY_KEY . " = %s",
            array('integer'),
            array($supervisor_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function insertSupervisor(array $values) : void
    {
        $primary_key = [self::SUPERVISOR_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SUPERVISOR_TABLE_NAME)]];
        $key_value = array_combine(self::SUPERVISOR_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::SUPERVISOR_TABLE_NAME, $primary_key + $key_value);
    }

    public function deleteSupervisorRows(string $supervisor_id) : void
    {
        $query = "DELETE FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SUPERVISOR_PRIMARY_KEY . " IN " . $supervisor_id;
        $this->ilDB->manipulate($query);
    }

    public function getAllLocationsByProtocolID(int $protocol_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getLocationsByLocationID(int $location_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT location FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::LOCATION_PRIMARY_KEY . " = %s",
            array('integer'),
            array($location_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function insertLocation(array $values) : void
    {
        // add auto increment to first array element
        $primary_key = [self::LOCATION_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::LOCATION_TABLE_NAME)]];
        $key_value = array_combine(self::LOCATION_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::LOCATION_TABLE_NAME, $primary_key + $key_value);
    }

    public function deleteLocationRows(string $location_ids) : void
    {
        $query = "DELETE FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::LOCATION_PRIMARY_KEY . " IN " . $location_ids;
        $this->ilDB->manipulate($query);
    }

    public function getAllParticipantsByProtocolID(int $protocol_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function insertParticipant(array $values) : void
    {
        $primary_key = [self::PARTICIPANTS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PARTICIPANTS_TABLE_NAME)]];
        $key_value = array_combine(self::PARTICIPANTS_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::PARTICIPANTS_TABLE_NAME, $primary_key + $key_value);
    }

    public function deleteParticipantRows(string $participant_ids) : void
    {
        $query = "DELETE FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::PARTICIPANTS_PRIMARY_KEY . " IN " . $participant_ids;
        $this->ilDB->manipulate($query);
    }

    public function getAllParticipantsByUserIDandFilter(string $usr_ids, string $login, string $name, string $mrt) : array
    {
        $query = $this->ilDB->queryF(
            "
            SELECT CONCAT(lastname, ', ', firstname) AS name, login, matriculation, email, usr_id
            FROM usr_data
            WHERE usr_id IN (" . $usr_ids . ")
              AND (COALESCE(%s, '') = '' OR login LIKE CONCAT('%%', %s, '%%' ))
              AND (COALESCE(%s, '') = '' OR matriculation LIKE CONCAT('%%', %s, '%%' ))
              AND (COALESCE(%s, '') = '' OR CONCAT(lastname, ', ', firstname) LIKE CONCAT('%%', %s, '%%' ))",
            array('string', 'string', 'string', 'string', 'string', 'string'),
            array($login, $login, $mrt, $mrt, $name, $name)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getUserIDbyParticipantID(int $participant_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT usr_id FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::PARTICIPANTS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($participant_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getMatriculationByUserID(int $user_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT matriculation FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getLoginByUserID(int $user_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT login FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getUsernameByUserID(int $user_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT firstname, lastname, login FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function insertProtocolEntry(array $values) : int
    {
        $primary_key = [self::PROTOCOL_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PROTOCOL_TABLE_NAME)]];
        $key_value = array_combine(self::PROTOCOL_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::PROTOCOL_TABLE_NAME, $primary_key + $key_value);
        return $primary_key['entry_id'][1];
    }

    public function getAllProtocolEntries(int $entry_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = %s",
            array('integer'),
            array($entry_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getAllProtocolEntriesByProtocolID(int $protocol_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_TABLE_NAME . " WHERE protocol_id = %s",
            array('integer'),
            array($protocol_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function updateProtocolEntry(array $values, array $where) : void
    {
        $columns = array_combine(self::PROTOCOL_TABLE_FIELDS, $values);
        $this->ilDB->update(self::PROTOCOL_TABLE_NAME, $columns, $where);
    }

    public function deleteProtocolEntry(string $entry_id) : void
    {
        $query = "DELETE FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = " . $entry_id;
        $this->ilDB->manipulate($query);
    }

    public function deleteAllProtocolEntries(string $protocol_id) : void
    {
        $query = "DELETE FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = " . $protocol_id;
        $this->ilDB->manipulate($query);
    }

    public function insertProtocolParticipant(array $values) : void
    {
        $primary_key = [self::PROTOCOL_PARTICIPANT_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PROTOCOL_PARTICIPANT_TABLE_NAME)]];
        $key_value = array_combine(self::PROTOCOL_PARTICIPANT_FIELDS, $values);
        $this->ilDB->insert(self::PROTOCOL_PARTICIPANT_TABLE_NAME, $primary_key + $key_value);
    }

    public function deleteProtocolParticipant(string $propar_id) : void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::PROTOCOL_PARTICIPANT_PRIMARY_KEY . " = " . $propar_id;
        $this->ilDB->manipulate($query);
    }

    public function deleteAllProtocolParticipantByEntryId(string $entry_id) : void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = " . $entry_id;
        $this->ilDB->manipulate($query);
    }

    public function deleteAllProtocolParticipantByProtocolId(string $protocol_id) : void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = " . $protocol_id;
        $this->ilDB->manipulate($query);
    }

    public function getAllProtocolParticipants(int $entry_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE entry_id = %s",
            array('integer'),
            array($entry_id)
        );
        return $this->ilDB->fetchAll($query);
    }

    public function getAllProtocolParticipantIDs(int $entry_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT participant_id FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE entry_id = %s",
            array('integer'),
            array($entry_id)
        );
        return array_reduce( $this->ilDB->fetchAll($query), function ($carry, $item) {
            $carry[] = $item['participant_id'];
            return $carry;
        }, []);
    }

    public function getAllProtocolParticipantsByProtocolID(int $protocol_id) : array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE protocol_id = %s",
            array('integer'),
            array($protocol_id)
        );
        return $this->ilDB->fetchAll($query);
    }
}
