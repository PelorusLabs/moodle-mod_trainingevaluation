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
use mod_trainingevaluation\local\section_item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the add_item class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\add_item
 * @runTestsInSeparateProcesses
 */
final class add_item_test extends \externallib_advanced_testcase {
    protected function add_item(...$params) {
        $additem = add_item::execute(...$params);
        return external_api::clean_returnvalue(add_item::execute_returns(), $additem);
    }

    /**
     * Test add_item webservice.
     */
    public function test_add_item(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'description' => 'Test Description',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Add an item.
        $result = $this->add_item($trainingevaluation->id, $section->get('id'), 'Test Item', 'Test Description', true, 'textinput');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $this->assertEquals($section->get('id'), $result['sectionid']);

        // Verify item was created in database.
        $item = $DB->get_record('trainingevaluation_section_items', ['id' => $result['id']]);
        $this->assertNotFalse($item);
        $this->assertEquals('Test Item', $item->name);
        $this->assertEquals('textinput', $item->type);
        $this->assertEquals($section->get('id'), $item->sectionid);
        $this->assertEquals(0, $item->position);
        $this->assertEquals('Test Description', $item->description);
        $this->assertEquals(1, $item->isrequired);
    }

    /**
     * Test add_item with different item types.
     */
    public function test_add_item_different_types(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'description' => 'Test Description',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Add textinput item.

        $result1 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Text Input', 'Text Description', true, 'textinput');
        $this->assertTrue($result1['success']);

        // Add datepicker item.
        $result2 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Date Picker', 'Date Description', true, 'datepicker');
        $this->assertTrue($result2['success']);

        // Add selectmenu item.
        $result3 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Select Menu', 'Select Description', true, 'selectmenu');
        $this->assertTrue($result3['success']);

        // Verify all have correct types.
        $item1 = new section_item($result1['id']);
        $item2 = new section_item($result2['id']);
        $item3 = new section_item($result3['id']);

        $this->assertEquals('textinput', $item1->get('type'));
        $this->assertEquals('datepicker', $item2->get('type'));
        $this->assertEquals('selectmenu', $item3->get('type'));
    }

    /**
     * Test add_item with invalid type.
     */
    public function test_add_item_invalid_type(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'description' => 'Test Description',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Try to add item with invalid type.
        $this->expectException(\invalid_parameter_exception::class);
        $this->add_item($trainingevaluation->id, $section->get('id'), 'Invalid Item', null, false, 'invalid_type');
    }

    /**
     * Test add_item positions are sequential.
     */
    public function test_add_item_positions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'description' => 'Test Description',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Add three items.
        $result1 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Item 1', null, false, 'textinput');

        $result2 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Item 2', null, false, 'textinput');

        $result3 = $this->add_item($trainingevaluation->id, $section->get('id'), 'Item 3', null, false, 'textinput');

        // Verify positions.
        $item1 = new section_item($result1['id']);
        $item2 = new section_item($result2['id']);
        $item3 = new section_item($result3['id']);

        $this->assertEquals(0, $item1->get('position'));
        $this->assertEquals(1, $item2->get('position'));
        $this->assertEquals(2, $item3->get('position'));
    }

    /**
     * Test add_item without manage capability.
     */
    public function test_add_item_no_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'description' => 'Test Description',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        $this->add_item($trainingevaluation->id, $section->get('id'), 'Test Item', null, false, 'textinput');
    }
}
