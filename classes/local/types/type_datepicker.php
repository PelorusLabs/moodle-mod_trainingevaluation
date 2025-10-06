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

use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\section_item;
use stdClass;

/**
 * Date picker item type
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_datepicker extends base {
    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @return string
     */
    public function render_manage_form(stdClass $trainingevaluation, section_item $item, int $userid): string {
        return \html_writer::tag(
            'input',
            '',
            ['class' => 'mod-trainingevaluation-datepicker-form form-control', 'type' => 'date', 'disabled' => 'disabled']
        );
    }

    /**
     * Render evaluation form.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    public function render_evaluate_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string {
        $date = '';
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $date = $response->get('response');
        }

        return \html_writer::tag(
            'input',
            '',
            ['class' => 'mod-trainingevaluation-datepicker-form form-control', 'type' => 'date', 'autocomplete' => 'off',
                'value' => $date]
        );
    }

    /**
     * Render the user view form.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    public function render_user_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string {
        $date = '';
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $date = $response->get('response');
        }

        return \html_writer::tag(
            'input',
            '',
            ['class' => 'mod-trainingevaluation-datepicker-form form-control', 'type' => 'date', 'disabled' => 'disabled',
                'value' => s($date)]
        );
    }

    /**
     * Get the config structure for this type.
     *
     * @return array
     */
    public function get_config_structure(): array {
        return [];
    }
}
