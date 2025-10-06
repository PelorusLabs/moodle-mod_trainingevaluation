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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity creation/editing form for the mod_trainingevaluation plugin.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_trainingevaluation_mod_form extends moodleform_mod {
    /**
     * Define the form.
     *
     * @return void
     * @throws coding_exception
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('pluginname', 'trainingevaluation'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('advcheckbox', 'showlastmodified', get_string('showlastmodified', 'trainingevaluation'));
        $mform->addHelpButton('showlastmodified', 'showlastmodified', 'mod_trainingevaluation');

        // Standard Moodle course module elements (course, category, etc.).
        $this->standard_coursemodule_elements();

        // Standard Moodle form buttons.
        $this->add_action_buttons();
    }

    /**
     * Add completion rules.
     *
     * @return string[]
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;

        $mform->addElement(
            'advcheckbox',
            'completiononrequired',
            get_string('completiononrequired', 'trainingevaluation'),
            get_string('completiononrequired_desc', 'trainingevaluation')
        );

        return ['completiononrequired' . $this->suffix];
    }

    /**
     * Check if the completion rule is enabled.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return $data['completiononrequired' . $this->suffix] != 0;
    }
}
