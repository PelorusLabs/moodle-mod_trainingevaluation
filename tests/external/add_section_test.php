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
 * Tests for the add_section class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\add_section
 */
final class add_section_test extends \externallib_advanced_testcase {
    protected function add_section(...$params) {
        $addsection = add_section::execute(...$params);
        return external_api::clean_returnvalue(add_section::execute_returns(), $addsection);
    }

    /**
     * Test add_section webservice.
     */
    public function test_add_section(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Add a top-level section.
        $result = add_section::execute($trainingevaluation->id, 'Test Section', 0);
        $result = \core_external\external_api::clean_returnvalue(add_section::execute_returns(), $result);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);

        // Verify section was created in database.
        $section = $DB->get_record('trainingevaluation_sections', ['id' => $result['id']]);
        $this->assertNotFalse($section);
        $this->assertEquals('Test Section', $section->name);
        $this->assertEquals($trainingevaluation->id, $section->wtid);
        $this->assertNull($section->parentsection);
        $this->assertEquals(0, $section->position);
    }

    /**
     * Test add_section as subsection.
     */
    public function test_add_subsection(): void {
        global $DB;

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

        // Add subsection.
        $result = add_section::execute($trainingevaluation->id, 'Child Section', $parentsection->get('id'));
        $result = external_api::clean_returnvalue(add_section::execute_returns(), $result);

        $this->assertTrue($result['success']);

        // Verify subsection was created with correct parent.
        $subsection = $DB->get_record('trainingevaluation_sections', ['id' => $result['id']]);
        $this->assertEquals($parentsection->get('id'), $subsection->parentsection);
        $this->assertEquals(0, $subsection->position);
    }

    /**
     * Test add_section positions are sequential.
     */
    public function test_add_section_positions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Add three sections.
        $result1 = add_section::execute($trainingevaluation->id, 'Section 1', 0);
        $result1 = external_api::clean_returnvalue(add_section::execute_returns(), $result1);

        $result2 = add_section::execute($trainingevaluation->id, 'Section 2', 0);
        $result2 = external_api::clean_returnvalue(add_section::execute_returns(), $result2);

        $result3 = add_section::execute($trainingevaluation->id, 'Section 3', 0);
        $result3 = external_api::clean_returnvalue(add_section::execute_returns(), $result3);

        // Verify positions.
        $section1 = new section($result1['id']);
        $section2 = new section($result2['id']);
        $section3 = new section($result3['id']);

        $this->assertEquals(0, $section1->get('position'));
        $this->assertEquals(1, $section2->get('position'));
        $this->assertEquals(2, $section3->get('position'));
    }

    /**
     * Test add_section without manage capability.
     */
    public function test_add_section_no_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        add_section::execute($trainingevaluation->id, 'Test Section', 0);
    }
}
