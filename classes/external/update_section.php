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
 * External function to update section data.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_section extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'Section ID'),
                'name' => new external_value(PARAM_TEXT, 'Section name', VALUE_DEFAULT, null),
                'movement' => new external_value(PARAM_TEXT, 'Movement direction', VALUE_DEFAULT, null),
            ]
        );
    }

    /**
     * Update section data
     *
     * @param int $id section ID
     * @param string|null $name section name
     * @param string|null $movement movement direction
     * @return bool
     */
    public static function execute(int $id, string|null $name = null, string|null $movement = null) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['id' => $id, 'name' => $name, 'movement' => $movement]
        );

        $section = new section($params['id']);

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $section->get('wtid')], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        $section->set('name', $name ?? $section->get('name'));

        if ($params['movement'] == 'up') {
            $section->move_up();
        } else if ($params['movement'] == 'down') {
            $section->move_down();
        }

        return $section->update();
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
