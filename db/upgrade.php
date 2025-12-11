<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrades for the trainingevaluation module.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade mod_trainingevaluation.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_trainingevaluation_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025121101) {
        // Define field timemodified to be added to trainingevaluation.
        $table = new xmldb_table('trainingevaluation');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'completiononrequired');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->execute("UPDATE {trainingevaluation} SET timemodified = :time WHERE timemodified IS NULL", ['time' => time()]);

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'completiononrequired');
        $dbman->change_field_notnull($table, $field);

        // Trainingevaluation savepoint reached.
        upgrade_mod_savepoint(true, 2025121101, 'trainingevaluation');
    }

    return true;
}
