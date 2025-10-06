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

namespace mod_trainingevaluation\form;

use moodleform;

/**
 * File upload form
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fileupload_form extends moodleform {
    /**
     * File upload form definition.
     *
     * @return void
     */
    protected function definition() {
        $mform =& $this->_form;

        $itemid = $this->_customdata['itemid'];
        $userid = $this->_customdata['userid'];

        $mform->addElement('hidden', 'itemid', $itemid);
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement(
            'filemanager',
            'type_fileupload_filemanager',
            get_string('file'),
            null,
            ['accepted_types' => $this->_customdata['filetypes']]
        );

        $this->add_action_buttons();
    }
}
