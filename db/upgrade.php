<?php


defined('MOODLE_INTERNAL') || die();

/**
 * upgrade this star_checklist plugin database
 *
 * @param int $oldversion The old version of the participantform local plugin
 *
 * @return bool
 */
function xmldb_local_star_checklist_upgrade($oldversion)
{
    global $CFG, $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2022041403) {

        // Define table local_starchecklist to be created.
        $table = new xmldb_table('local_starchecklist');

        // Adding fields to table local_starchecklist.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('submission', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        // Adding keys to table local_starchecklist.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_starchecklist.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Star_checklist savepoint reached.
        upgrade_plugin_savepoint(true, 2022041403, 'local', 'star_checklist');
    }
}
