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

namespace mod_trainingevaluation\local\types;

use completion_info;
use core\exception\invalid_parameter_exception;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section_item;
use stdClass;

/**
 * Item type base class
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @return string
     */
    abstract public function render_manage_form(stdClass $trainingevaluation, section_item $item, int $userid): string;

    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    abstract public function render_user_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string;

    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    abstract public function render_evaluate_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string;

    /**
     * Save a user's response to this type.
     *
     * @param $cm
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @param string|null $responsedata
     * @return bool
     */
    public function save_response(
        $cm,
        stdClass $trainingevaluation,
        section_item $item,
        evaluation $evaluation,
        string|null $responsedata
    ): bool {
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $evaluation->get('userid'),
            'version' => $evaluation->get('version')]);
        if (!$response) {
            $data = new \stdClass();
            $data->itemid = $item->get('id');
            $data->userid = $evaluation->get('userid');
            $data->version = $evaluation->get('version');
            $data->response = $responsedata;

            $response = new response(0, $data);
            $response->create();
        } else {
            $response->set('response', $responsedata);
            $response->update();
        }

        // Update section item completion state.
        $completed = $this->has_user_completed($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'));
        $response->set('completed', $completed);
        $response->update();

        // Update the activity completion state.
        if ($completed) {
            $course = get_course($trainingevaluation->course);
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $trainingevaluation->completiononrequired) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $evaluation->get('userid'));
            }
        }

        return true;
    }

    /**
     * Get item type response data.
     *
     * @param int $itemid
     * @param int $userid
     * @param int $version
     * @return response|false
     */
    public function get_response(int $itemid, int $userid, int $version = 1): response|bool {
        return response::get_record(['itemid' => $itemid, 'userid' => $userid, 'version' => $version]);
    }

    /**
     * Helper to load config from DB as an associative array.
     *
     * @param section_item $item
     * @return array
     */
    public function get_config(section_item $item): array {
        global $DB;
        $records = $DB->get_records('trainingevaluation_item_config', ['itemid' => $item->get('id')]);
        $config = [];
        foreach ($records as $r) {
            $config[$r->name] = $r->value;
        }
        return $config;
    }

    /**
     * Get the config structure for this type.
     *
     * @return array
     */
    abstract public function get_config_structure(): array;

    /**
     * Validate the config for this type.
     *
     * @param array $config
     * @return array
     */
    public function validate_config(array $config): array {
        $structure = $this->get_config_structure();

        $validated = [];

        foreach ($config as $name => $value) {
            if (!array_key_exists($name, $structure)) {
                throw new invalid_parameter_exception('Invalid config name: ' . $name);
            }
            $validated[$name] = clean_param($value, $structure[$name]['type']);
        }

        return $validated;
    }

    /**
     * Helper to save config to DB from an associative array.
     *
     * @param section_item $item
     * @param array $config
     * @return void
     */
    public function save_config(section_item $item, array $config) {
        global $DB;

        $validated = $this->validate_config($config);

        if (empty($validated)) {
            return;
        }

        $DB->delete_records('trainingevaluation_item_config', ['itemid' => $item->get('id')]);
        foreach ($validated as $name => $value) {
            $DB->insert_record('trainingevaluation_item_config', [
                'itemid' => $item->get('id'),
                'name' => $name,
                'value' => $value,
            ]);
        }
    }

    /**
     * Has the user completed this item?
     *
     * @param int $itemid
     * @param int $userid
     * @param int $version
     * @return bool
     */
    public function has_user_completed(int $itemid, int $userid, int $version): bool {
        // Default to user has completed if response exists.
        return response::record_exists_select(
            "itemid = :itemid AND userid = :userid AND version = :version",
            ['itemid' => $itemid, 'userid' => $userid, 'version' => $version]
        );
    }
}
