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
use core\exception\moodle_exception;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * External function to save response data.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_response extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'itemid' => new external_value(PARAM_INT, 'Item ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'response' => new external_value(PARAM_TEXT, 'Response data', VALUE_DEFAULT, null),
            ]
        );
    }

    /**
     * Save response data
     *
     * @param int $itemid item ID
     * @param int $userid user ID
     * @param string|null $responsedata
     * @return bool
     */
    public static function execute(int $itemid, int $userid, string|null $responsedata) {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['itemid' => $itemid, 'userid' => $userid, 'response' => $responsedata]
        );

        $item = new section_item($params['itemid']);
        $section = new section($item->get('sectionid'));

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $section->get('wtid')], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        if (!evaluation::can_evaluate_user($context, $userid)) {
            throw new moodle_exception(
                'nopermissiontoevaluate',
                'mod_trainingevaluation',
            );
        }

        if (!$DB->record_exists('user', ['id' => $params['userid']])) {
            return false;
        }

        $evaluation = evaluation::get_active_evaluation($trainingevaluation->id, $userid);
        if ($evaluation === false) {
            $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $userid);
        }

        if ($evaluation->is_finalised() || !$evaluation->is_active()) {
            return false;
        }

        return $item->get_type_instance()->save_response($cm, $trainingevaluation, $item, $evaluation, $params['response']);
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
