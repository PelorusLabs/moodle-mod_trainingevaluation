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

namespace mod_trainingevaluation;

use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * Unit tests for training evaluation section items.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\local\section_item
 */
final class section_item_test extends \advanced_testcase {
    /**
     * Test item creation.
     */
    public function test_item_creation(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'description' => 'Test Description',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Verify item was created.
        $this->assertTrue($item->get('id') > 0);
        $this->assertEquals('Test Item', $item->get('name'));
        $this->assertEquals('textinput', $item->get('type'));
        $this->assertEquals($section->get('id'), $item->get('sectionid'));
        $this->assertEquals(0, $item->get('position'));
        $this->assertTrue($item->get('isrequired'));

        // Verify database record.
        $record = $DB->get_record('trainingevaluation_section_items', ['id' => $item->get('id')]);
        $this->assertNotFalse($record);
        $this->assertEquals('Test Item', $record->name);
    }

    /**
     * Test item deletion.
     */
    public function test_item_deletion(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item to Delete',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();
        $itemid = $item->get('id');

        // Delete the item.
        $result = $item->delete();
        $this->assertTrue($result);

        // Verify item was deleted.
        $record = $DB->get_record('trainingevaluation_section_items', ['id' => $itemid]);
        $this->assertFalse($record);
    }

    /**
     * Test item position change (move up).
     */
    public function test_item_move_up(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create three items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        $item3 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => false,
        ]);
        $item3->create();

        // Move item 3 up.
        $item3->move_up();

        // Verify positions changed.
        $item1->read();
        $item2->read();
        $item3->read();

        $this->assertEquals(0, $item1->get('position'));
        $this->assertEquals(2, $item2->get('position'));
        $this->assertEquals(1, $item3->get('position'));
    }

    /**
     * Test item position change (move down).
     */
    public function test_item_move_down(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create three items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        $item3 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => false,
        ]);
        $item3->create();

        // Move item 1 down.
        $item1->move_down();

        // Verify positions changed.
        $item1->read();
        $item2->read();
        $item3->read();

        $this->assertEquals(1, $item1->get('position'));
        $this->assertEquals(0, $item2->get('position'));
        $this->assertEquals(2, $item3->get('position'));
    }

    /**
     * Test item cannot move up from first position.
     */
    public function test_item_cannot_move_up_from_first(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create an item at position 0.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Try to move up from position 0.
        $item->move_up();

        // Position should remain 0.
        $item->read();
        $this->assertEquals(0, $item->get('position'));
    }

    /**
     * Test item cannot move down from last position.
     */
    public function test_item_cannot_move_down_from_last(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create two items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        // Try to move down from last position.
        $item2->move_down();

        // Position should remain 1.
        $item2->read();
        $this->assertEquals(1, $item2->get('position'));
    }

    /**
     * Test getting items from a section.
     */
    public function test_get_items_from_section(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create multiple items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        $item3 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => false,
        ]);
        $item3->create();

        // Get items from section.
        $items = $section->get_items();

        // Verify all items returned in order.
        $this->assertCount(3, $items);
        $this->assertEquals('Item 1', $items[0]->get('name'));
        $this->assertEquals('Item 2', $items[1]->get('name'));
        $this->assertEquals('Item 3', $items[2]->get('name'));
    }

    /**
     * Test get_section method returns correct section.
     */
    public function test_get_section(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
            'isrequired' => false,
        ]);
        $section->create();

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Get section from item.
        $retrievedsection = $item->get_section();

        // Verify correct section returned.
        $this->assertEquals($section->get('id'), $retrievedsection->get('id'));
        $this->assertEquals('Test Section', $retrievedsection->get('name'));
    }

    /**
     * Test get_max_position for items.
     */
    public function test_get_max_position(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $item2->create();

        // Max position should be 1.
        $this->assertEquals(1, $item2->get_max_position());
    }

    /**
     * Test reorder items method.
     */
    public function test_reorder_items(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create items with gaps in positions.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 1',
            'type' => 'textinput',
            'position' => 5,
            'isrequired' => false,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 10,
            'isrequired' => false,
        ]);
        $item2->create();

        // Reorder items.
        $section->reorder_items();

        // Reload and verify sequential positions.
        $items = $section->get_items();
        $this->assertEquals(0, $items[0]->get('position'));
        $this->assertEquals(1, $items[1]->get('position'));
    }

    /**
     * Test items in nested subsections.
     */
    public function test_items_in_nested_subsections(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create parent section.
        $parent = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Parent',
            'parentsection' => null,
            'position' => 0,
        ]);
        $parent->create();

        // Create subsection.
        $child = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Child',
            'parentsection' => $parent->get('id'),
            'position' => 0,
        ]);
        $child->create();

        // Create items in both sections.
        $parentitem = new section_item(0, (object)[
            'sectionid' => $parent->get('id'),
            'name' => 'Parent Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $parentitem->create();
        $parentitemid = $parentitem->get('id');

        $childitem = new section_item(0, (object)[
            'sectionid' => $child->get('id'),
            'name' => 'Child Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $childitem->create();
        $childitemid = $childitem->get('id');

        // Verify items belong to correct sections.
        $parentitems = $parent->get_items();
        $childitems = $child->get_items();

        $this->assertCount(1, $parentitems);
        $this->assertCount(1, $childitems);
        $this->assertEquals('Parent Item', $parentitems[0]->get('name'));
        $this->assertEquals('Child Item', $childitems[0]->get('name'));

        // Delete parent with child data.
        $parent->delete_with_child_data();

        // Verify both items deleted.
        $this->assertFalse($DB->get_record('trainingevaluation_section_items', ['id' => $parentitemid]));
        $this->assertFalse($DB->get_record('trainingevaluation_section_items', ['id' => $childitemid]));
    }

    /**
     * Test multiple item types in same section.
     */
    public function test_multiple_item_types(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create items of different types.
        $textinputitem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $textinputitem->create();

        $datepickeritem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Display Text Item',
            'type' => 'datepicker',
            'position' => 1,
            'isrequired' => false,
        ]);
        $datepickeritem->create();

        // Verify items and types.
        $items = $section->get_items();
        $this->assertCount(2, $items);
        $this->assertEquals('textinput', $items[0]->get('type'));
        $this->assertEquals('datepicker', $items[1]->get('type'));
    }
}
