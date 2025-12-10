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

namespace mod_trainingevaluation\local;

use core\context;

/**
 * Class for evaluations.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation extends \core\persistent {
    /**
     * Database data.
     */
    public const TABLE = 'trainingevaluation_evaluations';

    /**
     * Defines and returns the properties of the class.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'wtid' => [
                'type' => PARAM_INT,
                'description' => 'The trainingevaluation instance ID.',
            ],
            'userid' => [
                'type' => PARAM_INT,
                'description' => 'The user ID.',
            ],
            'finalised' => [
                'type' => PARAM_BOOL,
                'description' => 'Whether the evaluation has been finalised.',
                'default' => false,
            ],
            'finalisedby' => [
                'type' => PARAM_INT,
                'description' => 'User ID of the person who finalised the evaluation.',
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'timefinalised' => [
                'type' => PARAM_INT,
                'description' => 'The time the evaluation was finalised.',
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'version' => [
                'type' => PARAM_INT,
                'description' => 'The version of the evaluation.',
                'default' => 1,
            ],
            'active' => [
                'type' => PARAM_BOOL,
                'description' => 'Whether this is the active evaluation for the user.',
                'default' => true,
            ],
        ];
    }

    /**
     * Get or create an evaluation record for a user.
     *
     * @param int $wtid
     * @param int $userid
     * @param int $version
     * @return evaluation
     */
    public static function get_record_create_if_not_exists(int $wtid, int $userid, int $version = 1): evaluation {
        if (self::get_record(['wtid' => $wtid, 'userid' => $userid, 'version' => $version]) == null) {
            $data = new \stdClass();
            $data->wtid = $wtid;
            $data->userid = $userid;
            $evaluation = new evaluation(0, $data);
            $evaluation->create();
        } else {
            $evaluation = self::get_record(['wtid' => $wtid, 'userid' => $userid, 'version' => $version]);
        }
        return $evaluation;
    }

    /**
     * Can the current user evaluate this?
     *
     * @param context $context
     * @param int $userid
     * @return bool
     */
    public static function can_evaluate_user(context $context, int $userid): bool {
        global $USER;

        if (
            has_capability('mod/trainingevaluation:evaluate', $context) ||
            (has_capability('mod/trainingevaluation:evaluateself', $context) && $userid == $USER->id)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get the active evaluation record for a user.
     *
     * @param int $wtid
     * @param int $userid
     * @return evaluation|false
     */
    public static function get_active_evaluation(int $wtid, int $userid): evaluation|false {
        return self::get_record(['wtid' => $wtid, 'userid' => $userid, 'active' => true]);
    }

    /**
     * Get the number of versions of an evaluation.
     *
     * @param int $wtid
     * @param int $userid
     * @return int
     */
    public static function get_number_of_versions(int $wtid, int $userid): int {
        return self::count_records_select(
            'wtid = :wtid AND userid = :userid',
            ['wtid' => $wtid, 'userid' => $userid]
        );
    }

    /**
     * Is the evaluation finalised?
     *
     * @return bool
     */
    public function is_finalised(): bool {
        return $this->get('finalised');
    }

    /**
     * Is the evaluation active?
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->get('active');
    }

    /**
     * Finalise the evaluation.
     *
     * @return bool
     */
    public function finalise(): bool {
        global $USER;

        $this->set('finalised', 1);
        $this->set('finalisedby', $USER->id);
        $this->set('timefinalised', time());
        return $this->update();
    }

    /**
     * Create a new version (reassessment) of this evaluation.
     * Marks this evaluation as inactive and creates a new active evaluation.
     *
     * @return evaluation|false
     */
    public function create_new_version(): evaluation|false {
        global $DB;

        if (!$this->is_finalised() || !$this->is_active()) {
            return false;
        }

        $newversion = $this->get('version') + 1;

        $transaction = $DB->start_delegated_transaction();

        try {
            // Mark this evaluation as no longer active.
            $this->set('active', 0);
            $this->update();

            // Create new evaluation version as active.
            $newevaluation = new self(0, (object) [
                'wtid' => $this->get('wtid'),
                'userid' => $this->get('userid'),
                'version' => $newversion,
                'active' => true,
                'finalised' => false,
            ]);

            $newevaluation->create();

            $transaction->allow_commit();

            return $newevaluation;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Get total number of items required for this evaluation.
     *
     * @return int
     */
    public function get_total_items_required(): int {
        global $DB;
        $sql = "SELECT COUNT(si.id) as total
                FROM {trainingevaluation_section_items} si
                JOIN {trainingevaluation_sections} s ON s.id = si.sectionid
                WHERE s.wtid = :wtid AND si.isrequired = 1";

        return (int) $DB->get_field_sql($sql, ['wtid' => $this->get('wtid')]);
    }

    /**
     * Get the total number of items completed by this user.
     *
     * @return int
     */
    public function get_total_items_completed(): int {
        global $DB;

        $sql = "SELECT
                    COUNT(DISTINCT r.itemid) as completed_count
                FROM {trainingevaluation_evaluations} eva
                JOIN {trainingevaluation_sections} s
                    ON s.wtid = eva.wtid
                JOIN {trainingevaluation_section_items} si
                    ON si.sectionid = s.id
                JOIN {trainingevaluation_responses} r
                    ON r.itemid = si.id AND r.userid = eva.userid AND r.version = eva.version
                WHERE
                    eva.wtid = :wtid
                    AND eva.userid = :userid
                    AND eva.version = :version
                    AND si.isrequired = 1
                    AND r.completed = 1
                GROUP BY eva.userid";

        return (int) $DB->get_field_sql(
            $sql,
            [
                'wtid' => $this->get('wtid'),
                'userid' => $this->get('userid'),
                'version' => $this->get('version'),
            ]
        );
    }
}
