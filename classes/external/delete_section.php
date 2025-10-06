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

namespace mod_trainingevaluation\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use mod_trainingevaluation\local\section;

/**
 * External function to delete a section and any associated data.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_section extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'Section ID'),
            ]
        );
    }

    /**
     * Delete a section and any associated data
     *
     * @param int $id section ID
     * @return bool
     */
    public static function execute(int $id) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['id' => $id]
        );

        $section = new section($params['id']);

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $section->get('wtid')], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        $result = $section->delete_with_child_data();
        if ($section->get('parentsection') != null) {
            $parentsection = new section($section->get('parentsection'));
            $parentsection->reorder_subsection_positions();
        } else {
            // Reorder top level sections.
            $position = 0;
            foreach (section::get_records([], 'position') as $subsection) {
                $subsection->set('position', $position);
                $subsection->update();
                $position++;
            }
        }
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, 'Status of the operation');
    }
}
