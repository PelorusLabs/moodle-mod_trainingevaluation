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
use core_external\external_single_structure;
use core_external\external_value;
use mod_trainingevaluation\local\section;

/**
 * External function to add a section to training evaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_section extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'wtid' => new external_value(PARAM_INT, 'Training Evaluation ID'),
                'name' => new external_value(PARAM_TEXT, 'Section name'),
                'parentsection' => new external_value(PARAM_INT, 'Parent section ID (0 for top-level sections)', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Create a new section
     *
     * @param int $wtid Training Evaluation ID
     * @param string $name Section name
     * @param int|null $parentsection Parent section ID (0 for top-level sections)
     * @return array
     */
    public static function execute(int $wtid, string $name, int|null $parentsection = 0) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['wtid' => $wtid, 'name' => $name, 'parentsection' => $parentsection]
        );

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $params['wtid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        $issubsection = $params['parentsection'] > 0;

        $data = new \stdClass();
        $data->wtid = $trainingevaluation->id;
        $data->name = $params['name'];

        if ($issubsection) {
            // Check that the parent section exists and belongs to this training evaluation instance.
            $parentsection = $DB->get_record(
                'trainingevaluation_sections',
                ['id' => $params['parentsection'], 'wtid' => $params['wtid']],
                '*',
                MUST_EXIST
            );

            // Get the max position for subsections under this parent.
            $maxposition = $DB->get_field_sql(
                'SELECT MAX(position) FROM {trainingevaluation_sections}
                 WHERE parentsection = ?',
                [$parentsection->id]
            );
            $data->parentsection = $parentsection->id;
        } else {
            // Get the max position for top-level sections.
            $maxposition = $DB->get_field_sql(
                'SELECT MAX(position) FROM {trainingevaluation_sections}
                 WHERE wtid = ? AND parentsection IS NULL',
                [$trainingevaluation->id]
            );
        }
        if ($maxposition == null) {
            $data->position = 0;
        } else {
            $data->position = $maxposition + 1;
        }
        $section = new section(0, $data);
        $section->create();

        return [
            'success' => true,
            'id' => $section->get('id'),
        ];
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Status of the operation'),
                'id' => new external_value(PARAM_INT, 'ID of the created section'),
            ]
        );
    }
}
