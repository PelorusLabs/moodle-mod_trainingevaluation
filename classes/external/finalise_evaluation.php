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

/**
 * External function to finalize a user's evaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finalise_evaluation extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'wtid' => new external_value(PARAM_INT, 'Training evaluation id'),
                'userid' => new external_value(PARAM_INT, 'User id'),
            ]
        );
    }

    /**
     * Finalise a user's evaluation.
     *
     * @param int $wtid
     * @param int $userid
     * @return bool
     */
    public static function execute(int $wtid, int $userid): bool {
        global $DB;

        $params = self::validate_parameters(
            self::execute_parameters(),
            ['wtid' => $wtid, 'userid' => $userid]
        );

        $trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $wtid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/trainingevaluation:finaliseevaluation', $context);

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

        if ($evaluation->is_finalised()) {
            return false;
        }

        return $evaluation->finalise();
    }

    /**
     * Returns description of method result value
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Status of the operation');
    }
}
