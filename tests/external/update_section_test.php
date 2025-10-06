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
 * Tests for the update_section class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\update_section
 */
final class update_section_test extends \externallib_advanced_testcase {
    protected function update_section(...$params) {
        $updatesection = update_section::execute(...$params);
        return external_api::clean_returnvalue(update_section::execute_returns(), $updatesection);
    }

    /**
     * Test update_section name.
     */
    public function test_update_section_name(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Original Name',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Update the name.
        $result = update_section::execute($section->get('id'), 'Updated Name', null);
        $this->assertTrue($result);

        // Verify name was updated.
        $section->read();
        $this->assertEquals('Updated Name', $section->get('name'));
    }

    /**
     * Test update_section move up.
     */
    public function test_update_section_move_up(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create sections.
        $section1 = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        // Move section 2 up.
        update_section::execute($section2->get('id'), null, 'up');

        // Verify positions swapped.
        $section1->read();
        $section2->read();
        $this->assertEquals(1, $section1->get('position'));
        $this->assertEquals(0, $section2->get('position'));
    }

    /**
     * Test update_section move down.
     */
    public function test_update_section_move_down(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create sections.
        $section1 = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        // Move section 1 down.
        update_section::execute($section1->get('id'), null, 'down');

        // Verify positions swapped.
        $section1->read();
        $section2->read();
        $this->assertEquals(1, $section1->get('position'));
        $this->assertEquals(0, $section2->get('position'));
    }
}
