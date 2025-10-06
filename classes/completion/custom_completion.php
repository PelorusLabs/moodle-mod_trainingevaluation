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

namespace mod_trainingevaluation\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion for training evaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Get the completion state for a given rule.
     *
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        switch ($rule) {
            case 'completiononrequired':
                $sql = "SELECT COUNT(si.id) as total
                        FROM {trainingevaluation_section_items} si
                        JOIN {trainingevaluation_sections} s ON s.id = si.sectionid
                        WHERE s.wtid = :wtid AND si.isrequired = 1";
                $totalrequired = (int) $DB->get_field_sql($sql, ['wtid' => $this->cm->instance]);

                if ($totalrequired === 0) {
                    return COMPLETION_COMPLETE;
                }

                $sql = "SELECT COUNT(DISTINCT r.itemid) as completed
                        FROM {trainingevaluation_responses} r
                        JOIN {trainingevaluation_evaluations} e ON e.userid = r.userid
                        JOIN {trainingevaluation_section_items} si ON si.id = r.itemid
                        JOIN {trainingevaluation_sections} s ON s.id = si.sectionid
                        WHERE s.wtid = :wtid
                        AND r.userid = :userid
                        AND si.isrequired = 1
                        AND r.completed = 1
                        AND e.active = 1";
                $completedcount = (int) $DB->get_field_sql($sql, ['wtid' => $this->cm->instance, 'userid' => $this->userid]);

                $status = $completedcount >= $totalrequired;
                break;
            default:
                $status = false;
                break;
        }

        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Retrieves custom-defined rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completiononrequired',
        ];
    }

    /**
     * Get the descriptions for custom rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completiononrequired' => get_string('completiononrequired', 'trainingevaluation'),
        ];
    }

    /**
     * Get the sort order for custom rules.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completiononrequired',
        ];
    }
}
