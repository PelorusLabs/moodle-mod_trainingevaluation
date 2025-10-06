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

use core_external\external_api;
use mod_trainingevaluation\local\section;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the get_section class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\get_section
 */
final class get_section_test extends \externallib_advanced_testcase {
    protected function get_section(...$params) {
        $getsection = get_section::execute(...$params);
        return external_api::clean_returnvalue(get_section::execute_returns(), $getsection);
    }

    /**
     * Test get_section webservice.
     */
    public function test_get_section(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Get the section via webservice.
        $result = get_section::execute($section->get('id'));
        $result = \core_external\external_api::clean_returnvalue(get_section::execute_returns(), $result);

        $this->assertEquals($section->get('id'), $result['id']);
        $this->assertEquals('Test Section', $result['name']);
        $this->assertEquals($trainingevaluation->id, $result['wtid']);
        $this->assertEquals(0, $result['position']);
    }

    /**
     * Test get_section with subsection.
     */
    public function test_get_subsection(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create parent section.
        $parentsection = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Parent Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $parentsection->create();

        // Create subsection.
        $subsection = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Child Section',
            'parentsection' => $parentsection->get('id'),
            'position' => 0,
        ]);
        $subsection->create();

        // Get the subsection via webservice.
        $result = get_section::execute($subsection->get('id'));
        $result = external_api::clean_returnvalue(get_section::execute_returns(), $result);

        $this->assertEquals($subsection->get('id'), $result['id']);
        $this->assertEquals($parentsection->get('id'), $result['parentsection']);
    }
}
