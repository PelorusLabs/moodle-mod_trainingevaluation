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
 * Text input item type
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_textinput extends base {
    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @return string
     */
    public function render_manage_form(stdClass $trainingevaluation, section_item $item, int $userid): string {
        $out = '';

        $placeholder = get_string('exampleinput', 'mod_trainingevaluation');
        $config = $this->get_config($item);
        if (isset($config['placeholdertext'])) {
            s($placeholder = $config['placeholdertext']);
        }

        $out .= \html_writer::tag(
            'textarea',
            '',
            ['class' => 'mod-trainingevaluation-textinput-form form-control', 'disabled' => 'disabled', 'autocomplete' => 'off',
                'placeholder' => $placeholder, 'rows' => $config['rows'] ?? 3]
        );

        return $out;
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
        $text = '';
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $text = $response->get('response');
        }

        $placeholder = '';
        $config = $this->get_config($item);
        if (isset($config['placeholdertext'])) {
            s($placeholder = $config['placeholdertext']);
        }

        return \html_writer::tag(
            'textarea',
            s($text),
            ['class' => 'mod-trainingevaluation-textinput-form form-control', 'autocomplete' => 'off', 'placeholder' => $placeholder,
                'maxlength' => $config['maxlength'] ?? '', 'rows' => $config['rows'] ?? 3]
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
        $text = '';
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $text = $response->get('response');
        }

        return \html_writer::tag(
            'textarea',
            s($text),
            ['class' => 'mod-trainingevaluation-textinput-form form-control', 'autocomplete' => 'off', 'disabled' => 'disabled',
                'rows' => $config['rows'] ?? 3]
        );
    }

    /**
     * Get the config structure for this type.
     *
     * @return array
     */
    public function get_config_structure(): array {
        return [
            'placeholdertext' => ['type' => PARAM_TEXT],
            'maxlength' => ['type' => PARAM_INT],
            'rows' => ['type' => PARAM_INT],
        ];
    }

    /**
     * Save the response for this item if the user has entered text.
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
        if (empty($responsedata)) {
            return false;
        }
        return parent::save_response($cm, $trainingevaluation, $item, $evaluation, $responsedata);
    }
}
