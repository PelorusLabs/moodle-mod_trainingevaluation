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
use dml_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * External function to get item data.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_item extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'Item ID'),
            ]
        );
    }

    /**
     * Get section item data
     *
     * @param int $id Section item ID
     * @return array
     * @throws dml_exception
     */
    public static function execute(int $id) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['id' => $id]
        );

        $item = new section_item($params['id']);
        $section = new section($item->get('sectionid'));

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $section->get('wtid')], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        return [
            'id' => $item->get('id'),
            'sectionid' => $item->get('sectionid'),
            'name' => $item->get('name'),
            'description' => $item->get('description'),
            'isrequired' => $item->get('isrequired'),
            'type' => $item->get('type'),
            'position' => $item->get('position'),
            'config' => json_encode($item->get_type_instance()->get_config($item)),
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
                'id' => new external_value(PARAM_INT, 'ID of the section'),
                'sectionid' => new external_value(PARAM_INT, 'ID of the section'),
                'name' => new external_value(PARAM_TEXT, 'Section name'),
                'description' => new external_value(PARAM_TEXT, 'Section description'),
                'isrequired' => new external_value(PARAM_BOOL, 'Is item required for completion'),
                'type' => new external_value(PARAM_TEXT, 'Item type'),
                'position' => new external_value(PARAM_INT, 'Position of the section'),
                'config' => new external_value(PARAM_TEXT, 'Item type config', VALUE_OPTIONAL),
            ]
        );
    }
}
