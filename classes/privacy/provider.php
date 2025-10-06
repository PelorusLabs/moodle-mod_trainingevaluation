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

namespace mod_trainingevaluation\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for trainingevaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\request\core_userlist_provider, \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider {
    /**
     * List of user data fields for trainingevaluation tables.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'trainingevaluation_sections',
            [
                'usermodified' => 'privacy:metadata:trainingevaluation_sections:usermodified',
            ],
            'privacy:metadata:trainingevaluation_sections'
        );
        $collection->add_database_table(
            'trainingevaluation_section_items',
            [
                'usermodified' => 'privacy:metadata:trainingevaluation_section_items:usermodified',
            ],
            'privacy:metadata:trainingevaluation_section_items'
        );
        $collection->add_database_table(
            'trainingevaluation_responses',
            [
                'usermodified' => 'privacy:metadata:trainingevaluation_responses:usermodified',
                'userid' => 'privacy:metadata:trainingevaluation_responses:userid',
            ],
            'privacy:metadata:trainingevaluation_responses'
        );
        $collection->add_database_table(
            'trainingevaluation_evaluations',
            [
                'usermodified' => 'privacy:metadata:trainingevaluation_evaluations:usermodified',
                'userid' => 'privacy:metadata:trainingevaluation_evaluations:userid',
                'finalisedby' => 'privacy:metadata:trainingevaluation_evaluations:finalisedby',
            ],
            'privacy:metadata:trainingevaluation_evaluations'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $contextlist->add_from_sql(
            "SELECT c.id
                    FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {trainingevaluation} wt ON wt.id = cm.instance
                LEFT JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                    WHERE (wts.usermodified = :userid)",
            [
                'modname' => 'trainingevaluation',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
            ]
        );
        $contextlist->add_from_sql(
            "SELECT c.id
                    FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {trainingevaluation} wt ON wt.id = cm.instance
                LEFT JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                LEFT JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                    WHERE (wtsi.usermodified = :userid)",
            [
                'modname' => 'trainingevaluation',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
            ]
        );
        $contextlist->add_from_sql(
            "SELECT c.id
                    FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {trainingevaluation} wt ON wt.id = cm.instance
                LEFT JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                LEFT JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                LEFT JOIN {trainingevaluation_responses} wtr ON wtr.itemid = wtsi.id
                    WHERE (wtr.usermodified = :usermodifiedid OR wtr.userid = :userid)",
            [
                'modname' => 'trainingevaluation',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
                'usermodifiedid' => $userid,
            ]
        );
        $contextlist->add_from_sql(
            "SELECT c.id
                    FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {trainingevaluation} wt ON wt.id = cm.instance
                LEFT JOIN {trainingevaluation_evaluations} wte ON wte.wtid = wt.id
                    WHERE (wte.usermodified = :usermodifiedid OR wte.userid = :userid OR wte.finalisedby = :userfinalisedid)",
            [
                'modname' => 'trainingevaluation',
                'contextlevel' => CONTEXT_MODULE,
                'userid' => $userid,
                'usermodifiedid' => $userid,
                'userfinalisedid' => $userid,
            ]
        );

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params = $contextparams;

        $sql = "SELECT
                    c.id AS contextid,
                    wts.name,
                    wtsi.name,
                    wtr.response,
                    wtr.completed
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid
                JOIN {trainingevaluation} wt ON wt.id = cm.instance
                JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                JOIN {trainingevaluation_responses} wtr ON wtr.itemid = wtsi.id
                WHERE (
	                wtr.userid = :userid AND
	                c.id IN $contextsql
                )";

        $params['userid'] = $userid;
        $responses = $DB->get_recordset_sql($sql, $params);

        foreach ($responses as $response) {
            $context = \context::instance_by_id($response->contextid);
            writer::with_context($context)->export_data([], $response);
        }
        $responses->close();
    }

    /**
     * Delete data for all users in a specific context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

        // Delete all responses for this trainingevaluation instance.
        $sql = "DELETE FROM {trainingevaluation_responses}
                 WHERE id IN (
                     SELECT wtr.id
                       FROM {trainingevaluation_responses} wtr
                       JOIN {trainingevaluation_section_items} wtsi ON wtsi.id = wtr.itemid
                       JOIN {trainingevaluation_sections} wts ON wts.id = wtsi.sectionid
                      WHERE wts.wtid = :wtid
                 )";
        $DB->execute($sql, ['wtid' => $instanceid]);

        // Delete all evaluations for this trainingevaluation instance.
        $DB->delete_records('trainingevaluation_evaluations', ['wtid' => $instanceid]);
    }

    /**
     * Delete all personal data for a user in a specific context.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

            $sql = "DELETE FROM {trainingevaluation_responses}
                     WHERE id IN (
                         SELECT wtr.id
                           FROM {trainingevaluation_responses} wtr
                           JOIN {trainingevaluation_section_items} wtsi ON wtsi.id = wtr.itemid
                           JOIN {trainingevaluation_sections} wts ON wts.id = wtsi.sectionid
                          WHERE wts.wtid = :wtid
                            AND wtr.userid = :userid
                     )";
            $DB->execute($sql, [
                'wtid' => $instanceid,
                'userid' => $userid,
            ]);

            $DB->delete_records('trainingevaluation_evaluations', [
                'wtid' => $instanceid,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Get all users in a context that have data.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $userlist->add_from_sql(
            'usermodified',
            "SELECT wts.usermodified
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'usermodified',
            "SELECT wtsi.usermodified
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                    JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT wtr.userid
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                    JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                    JOIN {trainingevaluation_responses} wtr ON wtr.itemid = wtsi.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'usermodified',
            "SELECT wtr.usermodified
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_sections} wts ON wts.wtid = wt.id
                    JOIN {trainingevaluation_section_items} wtsi ON wtsi.sectionid = wts.id
                    JOIN {trainingevaluation_responses} wtr ON wtr.itemid = wtsi.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'userid',
            "SELECT wte.userid
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_evaluations} wte ON wte.wtid = wt.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'usermodified',
            "SELECT wte.usermodified
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_evaluations} wte ON wte.wtid = wt.id
                        WHERE cm.id = :instanceid",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
        $userlist->add_from_sql(
            'finalisedby',
            "SELECT wte.finalisedby
                        FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                    JOIN {trainingevaluation} wt ON wt.id = cm.instance
                    JOIN {trainingevaluation_evaluations} wte ON wte.wtid = wt.id
                        WHERE cm.id = :instanceid AND wte.finalisedby IS NOT NULL",
            [
                'instanceid' => $context->instanceid,
                'modulename' => 'trainingevaluation',
            ]
        );
    }

    /**
     * Delete data for all specified users.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

        [$userinsql, $userinparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete responses for the specified users.
        $sql = "DELETE FROM {trainingevaluation_responses}
                 WHERE id IN (
                     SELECT wtr.id
                       FROM {trainingevaluation_responses} wtr
                       JOIN {trainingevaluation_section_items} wtsi ON wtsi.id = wtr.itemid
                       JOIN {trainingevaluation_sections} wts ON wts.id = wtsi.sectionid
                      WHERE wts.wtid = :wtid
                        AND wtr.userid {$userinsql}
                 )";

        $params = array_merge(['wtid' => $instanceid], $userinparams);
        $DB->execute($sql, $params);

        // Delete evaluations for the specified users.
        $DB->delete_records_select(
            'trainingevaluation_evaluations',
            "wtid = :wtid AND userid {$userinsql}",
            $params
        );
    }
}
