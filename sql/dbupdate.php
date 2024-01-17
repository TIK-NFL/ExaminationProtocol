<#1>
<?php
// Table names
$protocol_table_name = "tst_uihk_texa_protocol";
$supervisor_table_name = "tst_uihk_texa_supvis";
$location_table_name = "tst_uihk_texa_location";
$participants_table_name = "tst_uihk_texa_partic";
$settings_table_name = "tst_uihk_texa_general";
$protocol_participant_table_name = "tst_uihk_texa_propar";

// settings table
$settings = [
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'test_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_title' => [
        'type' => 'text',
        'length' => 400,
        'notnull' => false
    ],
    'protocol_desc' => [
        'type' => 'text',
        'length' => '400',
        'notnull' => false
    ],
    'type_exam' => [
        'type' => 'integer',
        'length' => 1,
        'notnull' => false,
        'default' => 0,
    ],
    'type_only_ilias' => [
        'type' => 'integer',
        'length' => 1,
        'notnull' => false,
        'default' => 0,
    ],
    'type_desc' => [
        'type' => 'text',
        'length' => '400',
        'notnull' => false
    ],
    'supervision' => [
        'type' => 'integer',
        'length' => 1,
        'notnull' => false,
        'default' => 0,
    ],
    'exam_policy' => [
        'type' => 'integer',
        'length' => 1,
        'notnull' => false,
        'default' => 0,
    ],
    'exam_policy_desc' => [
        'type' => 'text',
        'length' => '400',
        'notnull' => false
    ],
    'location' => [
        'type' => 'integer',
        'length' => 1,
        'notnull' => false,
        'default' => 0,
    ],
];

if(!$ilDB->tableExists($settings_table_name)) {
    $ilDB->createTable($settings_table_name, $settings);
    $ilDB->addPrimaryKey($settings_table_name, ["protocol_id"]);
    $ilDB->createSequence($settings_table_name);
}

//locations
$location = [
    'location_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'location' => [
        'type' => 'text',
        'length' => 100,
        'notnull' => false
    ],
];

if(!$ilDB->tableExists($location_table_name)) {
    $ilDB->createTable($location_table_name, $location);
    $ilDB->addPrimaryKey($location_table_name, ["location_id"]);
    $ilDB->createSequence($location_table_name);
}

// Supervision
$supervisors = [
    'supervisor_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'name' => [
        'type' => 'text',
        'length' => 100,
        'notnull' => false
    ],
];

if(!$ilDB->tableExists($supervisor_table_name)) {
    $ilDB->createTable($supervisor_table_name, $supervisors);
    $ilDB->addPrimaryKey($supervisor_table_name, ["supervisor_id"]);
    $ilDB->createSequence($supervisor_table_name);
}

// Examination Protocol
$protocol = [
    'entry_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'supervisor_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ],
    'location_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ],
    'start' => [
        'type' => 'timestamp',
        'notnull' => true
    ],
    'end' => [
        'type' => 'timestamp',
        'notnull' => true
    ],
    'creation' => [
        'type' => 'timestamp',
        'notnull' => true
    ],
    'event' => [
        'type' => 'text',
        'length' => '100',
        'notnull' => true
    ],
    'comment' => [
        'type' => 'text',
        'length' => '400',
        'notnull' => false
    ],
    'last_edit' => [
        'type' => 'timestamp',
        'notnull' => true
    ],
    'last_edited_by' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'created_by' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
];

if(!$ilDB->tableExists($protocol_table_name)) {
    $ilDB->createTable($protocol_table_name, $protocol);
    $ilDB->addPrimaryKey($protocol_table_name, ["entry_id"]);
    $ilDB->createSequence($protocol_table_name);
}

// participants
$participants = [
    'participant_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'usr_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
];

if(!$ilDB->tableExists($participants_table_name)) {
    $ilDB->createTable($participants_table_name, $participants);
    $ilDB->addPrimaryKey($participants_table_name, ["participant_id"]);
    $ilDB->createSequence($participants_table_name);
}

// protocol participants
$protocol_participant = [
    'propar_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'protocol_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'entry_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ],
    'participant_id' => [
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ]
];

if(!$ilDB->tableExists($protocol_participant_table_name)) {
    $ilDB->createTable($protocol_participant_table_name, $protocol_participant);
    $ilDB->addPrimaryKey($protocol_participant_table_name, ["propar_id"]);
    $ilDB->createSequence($protocol_participant_table_name);
}
?>

<#2>
<?php
$settings_table_name = "tst_uihk_texa_general";

$new_column = [
    'type' => 'text',
    'notnull' => false,
    'length' => 64,
    'default' => ''
];

// Check if the column already exists
if (!$ilDB->tableColumnExists($settings_table_name, 'resource_storage_id')) {
    // Add the new column
    $ilDB->addTableColumn($settings_table_name, 'resource_storage_id', $new_column);
}
?>
