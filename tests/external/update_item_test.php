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
 * Tests for the update_item class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\update_item
 */
final class update_item_test extends \externallib_advanced_testcase {
    protected function update_item(...$params) {
        $updateitem = update_item::execute(...$params);
        return external_api::clean_returnvalue(update_item::execute_returns(), $updateitem);
    }

    /**
     * Test update_item name.
     */
    public function test_update_item_name(): void {
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

        // Create an item.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Original Name',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Update the name.
        $result = update_item::execute($item->get('id'), 'Updated Name');
        $this->assertTrue($result);

        // Verify name was updated.
        $item->read();
        $this->assertEquals('Updated Name', $item->get('name'));
    }

    /**
     * Test update_item description.
     */
    public function test_update_item_description(): void {
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

        // Create an item.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Original Name',
            'description' => 'Test Description',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Update the description.
        $result = update_item::execute($item->get('id'), null, 'Updated Description');
        $this->assertTrue($result);

        // Verify description was updated.
        $item->read();
        $this->assertEquals('Updated Description', $item->get('description'));
    }

    /**
     * Test update_item move up.
     */
    public function test_update_item_move_up(): void {
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

        // Create items.
        $item1 = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $item2->create();

        // Move item 2 up.
        update_item::execute($item2->get('id'), null, null, null, 'up');

        // Verify positions swapped.
        $item1->read();
        $item2->read();
        $this->assertEquals(1, $item1->get('position'));
        $this->assertEquals(0, $item2->get('position'));
    }

    /**
     * Test update_item move down.
     */
    public function test_update_item_move_down(): void {
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

        // Create items.
        $item1 = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        // Move item 1 down.
        update_item::execute($item1->get('id'), null, null, null, 'down');

        // Verify positions swapped.
        $item1->read();
        $item2->read();
        $this->assertEquals(1, $item1->get('position'));
        $this->assertEquals(0, $item2->get('position'));
    }

    /**
     * Test update_item config.
     */
    public function test_update_item_config(): void {
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

        // Create an item.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Update config.
        $config = json_encode(['maxlength' => '200', 'placeholdertext' => 'Enter text', 'rows' => 5]);
        update_item::execute($item->get('id'), null, null, null, null, $config);

        // Verify config was saved.
        $typeinstance = $item->get_type_instance();
        $savedconfig = $typeinstance->get_config($item);
        $this->assertEquals('200', $savedconfig['maxlength']);
        $this->assertEquals('Enter text', $savedconfig['placeholdertext']);
        $this->assertEquals(5, $savedconfig['rows']);
    }
}
