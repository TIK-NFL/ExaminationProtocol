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
    const SETTINGS_PRIMARY_KEY = "protocol_id";

    /** @var string  */
    const TEST_ID_KEY = "test_id";
    /** @var string  */
    const SETTINGS_TABLE_NAME = "tst_uihk_texa_general";
    /** @var array */
    const SETTINGS_TABLE_FIELDS = ['test_id', 'protocol_title', 'protocol_desc', 'type_exam',
        'type_only_ilias', 'type_desc', 'supervision', 'exam_policy', 'exam_policy_desc', 'location',
        'resource_storage_id'];

    /** @var string  */
    const LOCATION_TABLE_NAME = "tst_uihk_texa_location";
    /** @var string */
    const LOCATION_PRIMARY_KEY = "location_id";
    /** @var array */
    const LOCATION_TABLE_FIELDS = ['protocol_id', 'location'];

    /** @var string  */
    const SUPERVISOR_TABLE_NAME = "tst_uihk_texa_supvis";
    /** @var string */
    const SUPERVISOR_PRIMARY_KEY = "supervisor_id";
    /** @var array */
    const SUPERVISOR_TABLE_FIELDS = ['protocol_id', 'name'];

    /** @var string  */
    const PROTOCOL_TABLE_NAME = "tst_uihk_texa_protocol";
    /** @var string */
    const PROTOCOL_PRIMARY_KEY = "entry_id";
    /** @var array */
    const PROTOCOL_TABLE_FIELDS = ['protocol_id', 'supervisor_id', 'location_id',
        'start', 'end', 'creation', 'event', 'comment', 'last_edit', 'last_edited_by', 'created_by'];

    /** @var string  */
    const PROTOCOL_PARTICIPANT_TABLE_NAME = "tst_uihk_texa_propar";
    /** @var string */
    const PROTOCOL_PARTICIPANT_PRIMARY_KEY = "propar_id";
    /** @var array */
    const PROTOCOL_PARTICIPANT_FIELDS = ['protocol_id', 'entry_id', 'participant_id'];

    /** @var string  */
    const PARTICIPANTS_TABLE_NAME = "tst_uihk_texa_partic";
    /** @var string */
    const PARTICIPANTS_PRIMARY_KEY = "participant_id";
    /** @var array */
    const PARTICIPANTS_TABLE_FIELDS = ['protocol_id', 'usr_id'];

    /** @var ilDBInterface */
    private $ilDB;

    public function __construct()
    {
        global $ilDB;
        $this->ilDB = $ilDB;
    }

    // Examination Protocol settings
    /**
     * Inserts a new row into the settings table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return void
     */
    public function insertSetting(array $values): void
    {
        $primaryKey = [self::SETTINGS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SETTINGS_TABLE_NAME)]];
        $keyValue = array_combine(self::SETTINGS_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::SETTINGS_TABLE_NAME, $primaryKey + $keyValue);
    }

    /**
     * @param array $values
     * @return void
     */
    public function createEmptySetting(array $values): void
    {
        $primaryKey = [self::SETTINGS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SETTINGS_TABLE_NAME)]];
        $keyValue = array_combine([self::SETTINGS_TABLE_FIELDS[0]], $values);
        $this->ilDB->insert(self::SETTINGS_TABLE_NAME, $primaryKey + $keyValue);
    }

    /**
     * Update specified columns in the settings table using the provided key-value pairs and a WHERE clause.
     * @param array $values An array of the new values.
     * @param array $where An associative array of column names and their values to use as a WHERE clause to identify the row to update.
     * @return void
     */
    public function updateSetting(array $values, array $where): void
    {
        $columns = array_combine(self::SETTINGS_TABLE_FIELDS, $values);
        $this->ilDB->update(self::SETTINGS_TABLE_NAME, $columns, $where);
    }

    /**
     * Get the setting row for a Test ID.
     * @param $test_id
     * @return array
     */
    public function getSettingByTestID($test_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );
        return (array) $this->ilDB->fetchObject($query);
    }

    /**
     * Tests if a setting exists
     * @param $test_id
     * @return bool
     */
    public function settingsExistByTestID($test_id): bool
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

    /**
     * Get the setting ID by Test ID.
     * @param $test_id
     * @return string|null
     */
    public function getProtocolIDByTestID($test_id): ?string
    {
        $query = $this->ilDB->queryF(
            "SELECT protocol_id FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );
        $result = (array) $this->ilDB->fetchObject($query);
        if (isset($result)) {
            return $result['protocol_id'];
        }
        return null;
    }

    public function getResourceIDbyTestID($test_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT resource_storage_id FROM " . self::SETTINGS_TABLE_NAME . " WHERE " . self::TEST_ID_KEY . " = %s",
            array('integer'),
            array($test_id)
        );

        return (array) $this->ilDB->fetchObject($query);
    }

    public function setResourceIDbyTestID($test_id, $resource_id) : void
    {
        $query = "UPDATE " . self::SETTINGS_TABLE_NAME . " SET resource_storage_id = '".$resource_id."' WHERE " . self::TEST_ID_KEY . "= ".$test_id.";";
        $this->ilDB->manipulate($query);
    }

    public function getTestTitleById($test_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT od.title FROM tst_tests AS tt, object_data AS od WHERE tt.test_id = %s AND tt.obj_fi = od.obj_id",
            array('integer'),
            array($test_id)
        );
        return (array) $this->ilDB->fetchObject($query);
    }

    // Examination Supervisors
    /**
     * Get the supervisors for by protocol ID.
     * @param $protocol_id
     * @return array of supervisors
     */
    public function getAllSupervisorsByProtocolID($protocol_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $supervisor_id
     * @return array
     */
    public function getSupervisorBySupervisorID($supervisor_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT name FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SUPERVISOR_PRIMARY_KEY . " = %s",
            array('integer'),
            array($supervisor_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }
    /**
     * Inserts a new row into the supervisor table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return void
     */
    public function insertSupervisor(array $values): void
    {
        $primary_key = [self::SUPERVISOR_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::SUPERVISOR_TABLE_NAME)]];
        $key_value = array_combine(self::SUPERVISOR_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::SUPERVISOR_TABLE_NAME, $primary_key + $key_value);
    }

    /**
     * Deletes one or more rows from the supervisor table database table, based on the given supervisor ID(s).
     * @param string $supervisor_id An array containing one or more supervisor ID(s) to be used in the SQL query's 'IN' clause.
     * @return void This function does not return anything.
     */
    public function deleteSupervisorRows(string $supervisor_id): void
    {
        $query = "DELETE FROM " . self::SUPERVISOR_TABLE_NAME . " WHERE " . self::SUPERVISOR_PRIMARY_KEY . " IN " . $supervisor_id;
        $this->ilDB->manipulate($query);
    }

    // Location of the examinations
    /**
     * Get the Locations by protocol ID.
     * @param $protocol_id
     * @return array of Locations
     */
    public function getAllLocationsByProtocolID($protocol_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $location_id
     * @return array
     */
    public function getLocationsByLocationID($location_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT location FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::LOCATION_PRIMARY_KEY . " = %s",
            array('integer'),
            array($location_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * Inserts a new row into the location table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return void
     */
    public function insertLocation(array $values): void
    {
        $primary_key = [self::LOCATION_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::LOCATION_TABLE_NAME)]];
        $key_value = array_combine(self::LOCATION_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::LOCATION_TABLE_NAME, $primary_key + $key_value);
    }

    /**
     *
     * @param string $location_ids
     * @return void
     */
    public function deleteLocationRows(string $location_ids): void
    {
        $query = "DELETE FROM " . self::LOCATION_TABLE_NAME . " WHERE " . self::LOCATION_PRIMARY_KEY . " IN " . $location_ids;
        $this->ilDB->manipulate($query);
    }

    // participants
    /**
     * Get the Locations by protocol ID.
     * @param $protocol_id
     * @return array of Locations
     */
    public function getAllParticipantsByProtocolID($protocol_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($protocol_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * Inserts a new row into the participant table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return void
     */
    public function insertParticipant(array $values): void
    {
        $primary_key = [self::PARTICIPANTS_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PARTICIPANTS_TABLE_NAME)]];
        $key_value = array_combine(self::PARTICIPANTS_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::PARTICIPANTS_TABLE_NAME, $primary_key + $key_value);
    }

    /**
     *
     * @param string $participant_ids
     * @return void
     */
    public function deleteParticipantRows(string $participant_ids): void
    {
        $query = "DELETE FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::PARTICIPANTS_PRIMARY_KEY . " IN " . $participant_ids;
        $this->ilDB->manipulate($query);
    }

    /**
     * @param $usr_ids
     * @param $login
     * @param $name
     * @param $mrt
     * @return array
     */
    public function getAllParticipantsByUserIDandFilter($usr_ids, $login, $name, $mrt): array
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
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $participant_id
     * @return array
     */
    public function getUserIDbyParticipantID($participant_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT usr_id FROM " . self::PARTICIPANTS_TABLE_NAME . " WHERE " . self::PARTICIPANTS_PRIMARY_KEY . " = %s",
            array('integer'),
            array($participant_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getMatriculationByUserID($user_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT matriculation FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getLoginByUserID($user_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT login FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getUsernameByUserID($user_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT firstname, lastname, login FROM usr_data WHERE usr_id = %s",
            array('integer'),
            array($user_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    // Examination protocol entries
    /**
     * Inserts a new row into the protocol table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return string row index of current entry
     */
    public function insertProtocolEntry(array $values): string
    {
        $primary_key = [self::PROTOCOL_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PROTOCOL_TABLE_NAME)]];
        $key_value = array_combine(self::PROTOCOL_TABLE_FIELDS, $values);
        $this->ilDB->insert(self::PROTOCOL_TABLE_NAME, $primary_key + $key_value);
        return $primary_key['entry_id'][1];
    }

    /**
     * @param $entry_id
     * @return array
     */
    public function getAllProtocolEntries($entry_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = %s",
            array('integer'),
            array($entry_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $protocol_id
     * @return array
     */
    public function getAllProtocolEntriesByProtocolID($protocol_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_TABLE_NAME . " WHERE protocol_id = %s",
            array('integer'),
            array($protocol_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * Update specified columns in the protocol table using the provided key-value pairs and a WHERE clause.
     * @param array $values An associative array of values for the columns.
     * @param array $where An associative array of column names and their values to use as a WHERE clause to identify the row to update.
     * @return void
     */
    public function updateProtocolEntry(array $values, array $where): void
    {
        $columns = array_combine(self::PROTOCOL_TABLE_FIELDS, $values);
        $this->ilDB->update(self::PROTOCOL_TABLE_NAME, $columns, $where);
    }

    /**
     * @param string $entry_id
     * @return void
     */
    public function deleteProtocolEntry(string $entry_id): void
    {
        $query = "DELETE FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = " . $entry_id;
        $this->ilDB->manipulate($query);
    }

    /**
     * @param string $protocol_id
     * @return void
     */
    public function deleteAllProtocolEntries(string $protocol_id): void
    {
        $query = "DELETE FROM " . self::PROTOCOL_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = " . $protocol_id;
        $this->ilDB->manipulate($query);
    }

    /**
     * Inserts a new row into the protocol participant table using the provided key-value pairs.
     * @param array $values An array of values to be inserted into the settings table.
     * @return void
     */
    public function insertProtocolParticipant(array $values): void
    {
        $primary_key = [self::PROTOCOL_PARTICIPANT_PRIMARY_KEY => ['integer', $this->ilDB->nextId(self::PROTOCOL_PARTICIPANT_TABLE_NAME)]];
        $key_value = array_combine(self::PROTOCOL_PARTICIPANT_FIELDS, $values);
        $this->ilDB->insert(self::PROTOCOL_PARTICIPANT_TABLE_NAME, $primary_key + $key_value);
    }

    /**
     *
     * @param string $propar_id
     * @return void
     */
    public function deleteProtocolParticipant(string $propar_id): void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::PROTOCOL_PARTICIPANT_PRIMARY_KEY . " = " . $propar_id;
        $this->ilDB->manipulate($query);
    }

    /**
     * @param string $entry_id
     * @return void
     */
    public function deleteAllProtocolParticipantByEntryId(string $entry_id): void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::PROTOCOL_PRIMARY_KEY . " = " . $entry_id;
        $this->ilDB->manipulate($query);
    }

    /**
     * @param string $protocol_id
     * @return void
     */
    public function deleteAllProtocolParticipantByProtocolId(string $protocol_id): void
    {
        $query = "DELETE FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE " . self::SETTINGS_PRIMARY_KEY . " = " . $protocol_id;
        $this->ilDB->manipulate($query);
    }

    /**
     * @param $entry_id
     * @return array
     */
    public function getAllProtocolParticipants($entry_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE entry_id = %s",
            array('integer'),
            array($entry_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }

    /**
     * @param $entry_id
     * @return array
     */
    public function getAllProtocolParticipantIDs($entry_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT participant_id FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE entry_id = %s",
            array('integer'),
            array($entry_id)
        );
        return array_reduce((array) $this->ilDB->fetchAll($query), function ($carry, $item) {
            $carry[] = $item['participant_id'];
            return $carry;
        }, []);
    }

    /**
     * @param $protocol_id
     * @return array
     */
    public function getAllProtocolParticipantsByProtocolID($protocol_id): array
    {
        $query = $this->ilDB->queryF(
            "SELECT * FROM " . self::PROTOCOL_PARTICIPANT_TABLE_NAME . " WHERE protocol_id = %s",
            array('integer'),
            array($protocol_id)
        );
        return (array) $this->ilDB->fetchAll($query);
    }
}
