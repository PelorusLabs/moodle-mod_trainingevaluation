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
use dml_exception;
use invalid_parameter_exception;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * External function to add an item to a section.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_item extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'wtid' => new external_value(PARAM_INT, 'Training Evaluation ID'),
                'sectionid' => new external_value(PARAM_INT, 'Section ID'),
                'name' => new external_value(PARAM_TEXT, 'Item name'),
                'description' => new external_value(PARAM_TEXT, 'Item description', VALUE_DEFAULT, null),
                'isrequired' => new external_value(PARAM_BOOL, 'Is item required for completion'),
                'type' => new external_value(PARAM_TEXT, 'Item type'),
            ]
        );
    }

    /**
     * Create a new item in a section
     *
     * @param int $wtid Training Evaluation ID
     * @param int $sectionid Section ID
     * @param string $name Item name
     * @param string|null $description Item description
     * @param bool $isrequired Is item required for completion
     * @param string $type Item type
     * @return array
     * @throws dml_exception
     */
    public static function execute(
        int $wtid,
        int $sectionid,
        string $name,
        string|null $description,
        bool $isrequired,
        string $type
    ) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'wtid' => $wtid,
                'sectionid' => $sectionid,
                'name' => $name,
                'description' => $description,
                'isrequired' => $isrequired,
                'type' => $type,
            ]
        );

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $params['wtid']], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        $section = new section($params['sectionid']);

        // TODO: Load list dynamically?
        if (!in_array($params['type'], ['textinput', 'selectmenu', 'fileupload', 'datepicker'])) {
            throw new invalid_parameter_exception('Unexpected item type received.');
        }

        $data = new \stdClass();
        $data->sectionid = $section->get('id');
        $data->name = $params['name'];
        $data->description = $params['description'];
        $data->isrequired = $params['isrequired'];
        $data->type = $params['type'];

        // Get the max position for items in this section.
        $maxposition = $DB->get_field_sql(
            'SELECT MAX(position) FROM {trainingevaluation_section_items}
             WHERE sectionid = ?',
            [$section->get('id')]
        );
        if ($maxposition == null) {
            $data->position = 0;
        } else {
            $data->position = $maxposition + 1;
        }

        $item = new section_item(0, $data);
        $item->create();

        return [
            'success' => true,
            'id' => $item->get('id'),
            'sectionid' => $section->get('id'),
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
                'id' => new external_value(PARAM_INT, 'ID of the created item'),
                'sectionid' => new external_value(PARAM_INT, 'ID of the section'),
            ]
        );
    }
}
