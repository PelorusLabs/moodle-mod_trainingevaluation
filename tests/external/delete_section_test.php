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
 * Tests for the delete_section class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\delete_section
 */
final class delete_section_test extends \externallib_advanced_testcase {
    protected function delete_section(...$params) {
        $deletesection = delete_section::execute(...$params);
        return external_api::clean_returnvalue(delete_section::execute_returns(), $deletesection);
    }

    /**
     * Test delete_section webservice.
     */
    public function test_delete_section(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section to Delete',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();
        $sectionid = $section->get('id');

        // Delete the section.
        $result = delete_section::execute($sectionid);
        $this->assertTrue($result);

        // Verify section was deleted.
        $this->assertFalse($DB->record_exists('trainingevaluation_sections', ['id' => $sectionid]));
    }

    /**
     * Test delete_section with subsections and items.
     */
    public function test_delete_section_with_children(): void {
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

        // Create subsection.
        $subsection = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Child Section',
            'parentsection' => $parentsection->get('id'),
            'position' => 0,
        ]);
        $subsection->create();
        $subsectionid = $subsection->get('id');

        // Delete parent section.
        $parentid = $parentsection->get('id');
        delete_section::execute($parentid);

        // Verify both sections were deleted.
        $this->assertFalse($DB->record_exists('trainingevaluation_sections', ['id' => $parentid]));
        $this->assertFalse($DB->record_exists('trainingevaluation_sections', ['id' => $subsectionid]));
    }

    /**
     * Test delete_section reorders remaining sections.
     */
    public function test_delete_section_reorders(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create three sections.
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

        $section3 = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 3',
            'parentsection' => null,
            'position' => 2,
        ]);
        $section3->create();

        // Delete middle section.
        delete_section::execute($section2->get('id'));

        // Verify remaining sections are reordered.
        $section1->read();
        $section3->read();
        $this->assertEquals(0, $section1->get('position'));
        $this->assertEquals(1, $section3->get('position'));
    }
}
