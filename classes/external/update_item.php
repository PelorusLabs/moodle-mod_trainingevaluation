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
use core\exception\invalid_parameter_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * External function to update item data.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_item extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'Item ID'),
                'name' => new external_value(PARAM_TEXT, 'Item name', VALUE_DEFAULT, null),
                'description' => new external_value(PARAM_TEXT, 'Item description', VALUE_DEFAULT, null),
                'isrequired' => new external_value(PARAM_BOOL, 'Is item required for completion', VALUE_DEFAULT, null),
                'movement' => new external_value(PARAM_TEXT, 'Movement direction', VALUE_DEFAULT, null),
                'config' => new external_value(PARAM_RAW, 'Config', VALUE_DEFAULT, null),
            ]
        );
    }

    /**
     * Update item data
     *
     * @param int $id item ID
     * @param string|null $name item name
     * @param string|null $description item description
     * @param bool|null $isrequired Is item required for completion
     * @param string|null $movement movement direction
     * @param string|null $config config
     * @return bool
     */
    public static function execute(
        int $id,
        string|null $name = null,
        string|null $description = null,
        bool|null $isrequired = null,
        string|null $movement = null,
        string|null $config = null
    ) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['id' => $id, 'name' => $name, 'movement' => $movement, 'config' => $config, 'description' => $description,
                'isrequired' => $isrequired]
        );

        $item = new section_item($params['id']);
        $section = new section($item->get('sectionid'));

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $section->get('wtid')], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:manage', $context);

        $item->set('name', $name ?? $item->get('name'));
        $item->set('description', $params['description'] ?? $item->get('description'));
        $item->set('isrequired', $params['isrequired'] ?? $item->get('isrequired'));

        if ($params['movement'] == 'up') {
            $item->move_up();
        } else if ($params['movement'] == 'down') {
            $item->move_down();
        }

        if ($params['config'] != null) {
            $typeconfig = json_decode($params['config'], true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($typeconfig)) {
                throw new invalid_parameter_exception('Config must be a JSON object');
            }

            $item->get_type_instance()->save_config($item, $typeconfig);
        }

        return $item->update();
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
