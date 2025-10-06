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

use core\exception\invalid_parameter_exception;
use core\output\html_writer;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\section_item;
use stdClass;

/**
 * Select menu item type
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_selectmenu extends base {
    /**
     * Render the input form for this type.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @return string
     */
    public function render_manage_form(stdClass $trainingevaluation, section_item $item, int $userid): string {
        $config = $this->get_config($item);

        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-selectmenu-options']);

        $options = [];
        if (isset($config['options'])) {
            $options = $config['options'];
        }

        foreach ($options as $i => $option) {
            $out .= html_writer::tag(
                'input',
                '',
                ['id' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i, 'class' => 'mod-trainingevaluation-selectmenu-option',
                    'value' => $option['value'], 'type' => 'radio',
                'name' => 'mod-trainingevaluation-selectmenu-' . $item->get('id'),
                'disabled' => 'disabled']
            );
            $out .= html_writer::tag('label', $option['value'], ['class' => 'mod-trainingevaluation-selectmenu-option-label',
                'for' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i]);
        }

        $out .= html_writer::end_tag('div');

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
        $config = $this->get_config($item);

        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-selectmenu-options']);

        $optionid = null;
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $optionid = intval($response->get('response'));
        }

        $options = [];
        if (isset($config['options'])) {
            $options = $config['options'];
        }

        foreach ($options as $i => $option) {
            $attrs = ['id' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i,
                'class' => 'mod-trainingevaluation-selectmenu-option',
                'value' => $option['value'],
                'type' => 'radio',
                'name' => 'mod-trainingevaluation-selectmenu-' . $item->get('id'),
                'data-option-id' => $option['id'],
            ];
            if ($optionid === $option['id']) {
                $attrs['checked'] = 'checked';
            }
            $out .= html_writer::tag('input', '', $attrs);
            $out .= html_writer::tag('label', $option['value'], ['class' => 'mod-trainingevaluation-selectmenu-option-label',
                'for' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i]);
        }

        $out .= html_writer::end_tag('div');

        return $out;
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
        $config = $this->get_config($item);

        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-selectmenu-options']);

        $optionid = null;
        if ($response = $this->get_response($item->get('id'), $evaluation->get('userid'), $evaluation->get('version'))) {
            $optionid = intval($response->get('response'));
        }

        $options = [];
        if (isset($config['options'])) {
            $options = $config['options'];
        }

        foreach ($options as $i => $option) {
            $attrs = ['id' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i,
                'class' => 'mod-trainingevaluation-selectmenu-option',
                'value' => s($option['value']),
                'type' => 'radio',
                'name' => 'mod-trainingevaluation-selectmenu-' . $item->get('id'),
                'data-option-id' => $option['id'],
                'disabled' => 'disabled',
            ];
            if ($optionid === $option['id']) {
                $attrs['checked'] = 'checked';
            }
            $out .= html_writer::tag('input', '', $attrs);
            $out .= html_writer::tag('label', s($option['value']), ['class' => 'mod-trainingevaluation-selectmenu-option-label',
                'for' => 'mod-trainingevaluation-selectmenu-option-' . $item->get('id') . '-' . $i]);
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Get the config structure for this type.
     *
     * @return array
     */
    public function get_config_structure(): array {
        return [
            'options' => ['type' => PARAM_RAW],
        ];
    }

    /**
     * Save the configuration for this item, a json encoded array of options.
     *
     * @param section_item $item
     * @param array $config
     * @return void
     */
    public function save_config(section_item $item, array $config) {
        if (isset($config['options'])) {
            if (is_array($config['options'])) {
                $validated = [];
                foreach ($config['options'] as $option) {
                    if (!isset($option['value']) || !isset($option['id'])) {
                        throw new invalid_parameter_exception('Options must be an array of objects with value and id properties');
                    }
                    $validated[] = [
                        'id' => clean_param($option['id'], PARAM_INT),
                        'value' => clean_param($option['value'], PARAM_TEXT),
                    ];
                }
                $config['options'] = json_encode($validated, JSON_UNESCAPED_UNICODE);
            }
        }

        parent::save_config($item, $config);
    }

    /**
     * Return and decode the options from the config.
     *
     * @param section_item $item
     * @return array
     */
    public function get_config(section_item $item): array {
        $config = parent::get_config($item);

        if (isset($config['options'])) {
            $config['options'] = json_decode($config['options'], true);
        }

        return $config;
    }
}
