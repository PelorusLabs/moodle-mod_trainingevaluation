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
 * Unit tests for training evaluation sections.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\local\section
 */
final class section_test extends \advanced_testcase {
    /**
     * Test section creation.
     */
    public function test_section_creation(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a top-level section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Verify section was created.
        $this->assertTrue($section->get('id') > 0);
        $this->assertEquals('Test Section', $section->get('name'));
        $this->assertEquals($trainingevaluation->id, $section->get('wtid'));
        $this->assertNull($section->get('parentsection'));
        $this->assertEquals(0, $section->get('position'));

        // Verify database record.
        $record = $DB->get_record('trainingevaluation_sections', ['id' => $section->get('id')]);
        $this->assertNotFalse($record);
        $this->assertEquals('Test Section', $record->name);
    }

    /**
     * Test section deletion.
     */
    public function test_section_deletion(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section to Delete',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();
        $sectionid = $section->get('id');

        // Delete the section.
        $result = $section->delete();
        $this->assertTrue($result);

        // Verify section was deleted.
        $record = $DB->get_record('trainingevaluation_sections', ['id' => $sectionid]);
        $this->assertFalse($record);
    }

    /**
     * Test section deletion with child data.
     */
    public function test_section_deletion_with_child_data(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create parent section.
        $parentsection = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Parent Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $parentsection->create();
        $parentsectionid = $parentsection->get('id');

        // Create subsection.
        $subsection = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Subsection',
            'parentsection' => $parentsectionid,
            'position' => 0,
        ]);
        $subsection->create();
        $subsectionid = $subsection->get('id');

        // Create items in both sections.
        $item1 = new section_item(0, (object)[
            'sectionid' => $parentsectionid,
            'name' => 'Parent Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();
        $item1id = $item1->get('id');

        $item2 = new section_item(0, (object)[
            'sectionid' => $subsectionid,
            'name' => 'Child Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item2->create();
        $item2id = $item2->get('id');

        // Delete parent section with child data.
        $result = $parentsection->delete_with_child_data();
        $this->assertTrue($result);

        // Verify parent section deleted.
        $this->assertFalse($DB->get_record('trainingevaluation_sections', ['id' => $parentsectionid]));

        // Verify subsection deleted.
        $this->assertFalse($DB->get_record('trainingevaluation_sections', ['id' => $subsectionid]));

        // Verify items deleted.
        $this->assertFalse($DB->get_record('trainingevaluation_section_items', ['id' => $item1id]));
        $this->assertFalse($DB->get_record('trainingevaluation_section_items', ['id' => $item2id]));
    }

    /**
     * Test section position change (move up).
     */
    public function test_section_move_up(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create three sections.
        $section1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        $section3 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 3',
            'parentsection' => null,
            'position' => 2,
        ]);
        $section3->create();

        // Move section 3 up.
        $section3->move_up();

        // Verify positions changed.
        $section1->read();
        $section2->read();
        $section3->read();

        $this->assertEquals(0, $section1->get('position'));
        $this->assertEquals(2, $section2->get('position'));
        $this->assertEquals(1, $section3->get('position'));
    }

    /**
     * Test section position change (move down).
     */
    public function test_section_move_down(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create three sections.
        $section1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        $section3 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 3',
            'parentsection' => null,
            'position' => 2,
        ]);
        $section3->create();

        // Move section 1 down.
        $section1->move_down();

        // Verify positions changed.
        $section1->read();
        $section2->read();
        $section3->read();

        $this->assertEquals(1, $section1->get('position'));
        $this->assertEquals(0, $section2->get('position'));
        $this->assertEquals(2, $section3->get('position'));
    }

    /**
     * Test section cannot move up from first position.
     */
    public function test_section_cannot_move_up_from_first(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Try to move up from position 0.
        $section->move_up();

        // Position should remain 0.
        $section->read();
        $this->assertEquals(0, $section->get('position'));
    }

    /**
     * Test section cannot move down from last position.
     */
    public function test_section_cannot_move_down_from_last(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        $section1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        // Try to move down from last position.
        $section2->move_down();

        // Position should remain 1.
        $section2->read();
        $this->assertEquals(1, $section2->get('position'));
    }

    /**
     * Test nested subsection creation.
     */
    public function test_nested_subsection_creation(): void {
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

        // Create first-level subsection.
        $child1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Child Level 1',
            'parentsection' => $parent->get('id'),
            'position' => 0,
        ]);
        $child1->create();

        // Create second-level subsection.
        $child2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Child Level 2',
            'parentsection' => $child1->get('id'),
            'position' => 0,
        ]);
        $child2->create();

        // Verify relationships.
        $this->assertNull($parent->get('parentsection'));
        $this->assertEquals($parent->get('id'), $child1->get('parentsection'));
        $this->assertEquals($child1->get('id'), $child2->get('parentsection'));

        // Verify get_subsections method.
        $parentsubsections = $parent->get_subsections();
        $this->assertCount(1, $parentsubsections);
        $this->assertEquals('Child Level 1', $parentsubsections[0]->get('name'));

        $child1subsections = $child1->get_subsections();
        $this->assertCount(1, $child1subsections);
        $this->assertEquals('Child Level 2', $child1subsections[0]->get('name'));
    }

    /**
     * Test nested subsection deletion cascades properly.
     */
    public function test_nested_subsection_deletion_cascade(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create three-level hierarchy.
        $level1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Level 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $level1->create();
        $level1id = $level1->get('id');

        $level2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Level 2',
            'parentsection' => $level1id,
            'position' => 0,
        ]);
        $level2->create();
        $level2id = $level2->get('id');

        $level3 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Level 3',
            'parentsection' => $level2id,
            'position' => 0,
        ]);
        $level3->create();
        $level3id = $level3->get('id');

        // Delete top-level section.
        $level1->delete_with_child_data();

        // Verify all levels deleted.
        $this->assertFalse($DB->get_record('trainingevaluation_sections', ['id' => $level1id]));
        $this->assertFalse($DB->get_record('trainingevaluation_sections', ['id' => $level2id]));
        $this->assertFalse($DB->get_record('trainingevaluation_sections', ['id' => $level3id]));
    }

    /**
     * Test subsection position ordering within parent.
     */
    public function test_subsection_position_ordering(): void {
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

        // Create three subsections.
        $sub1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 1',
            'parentsection' => $parent->get('id'),
            'position' => 0,
        ]);
        $sub1->create();

        $sub2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 2',
            'parentsection' => $parent->get('id'),
            'position' => 1,
        ]);
        $sub2->create();

        $sub3 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 3',
            'parentsection' => $parent->get('id'),
            'position' => 2,
        ]);
        $sub3->create();

        // Move sub3 up.
        $sub3->move_up();

        // Reload from database.
        $sub1->read();
        $sub2->read();
        $sub3->read();

        // Verify positions.
        $this->assertEquals(0, $sub1->get('position'));
        $this->assertEquals(2, $sub2->get('position'));
        $this->assertEquals(1, $sub3->get('position'));
    }

    /**
     * Test reorder subsection positions method.
     */
    public function test_reorder_subsection_positions(): void {
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

        // Create subsections with gaps in positions.
        $sub1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 1',
            'parentsection' => $parent->get('id'),
            'position' => 5,
        ]);
        $sub1->create();

        $sub2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 2',
            'parentsection' => $parent->get('id'),
            'position' => 10,
        ]);
        $sub2->create();

        // Reorder positions.
        $parent->reorder_subsection_positions();

        // Reload and verify sequential positions.
        $subsections = $parent->get_subsections();
        $this->assertEquals(0, $subsections[0]->get('position'));
        $this->assertEquals(1, $subsections[1]->get('position'));
    }

    /**
     * Test get_max_position for sections without parent.
     */
    public function test_get_max_position_top_level(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create top-level sections.
        $section1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 1',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section1->create();

        $section2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Section 2',
            'parentsection' => null,
            'position' => 1,
        ]);
        $section2->create();

        // Max position should be 1.
        $this->assertEquals(1, $section2->get_max_position());
    }

    /**
     * Test get_max_position for subsections.
     */
    public function test_get_max_position_subsections(): void {
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

        // Create subsections.
        $sub1 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 1',
            'parentsection' => $parent->get('id'),
            'position' => 0,
        ]);
        $sub1->create();

        $sub2 = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Sub 2',
            'parentsection' => $parent->get('id'),
            'position' => 1,
        ]);
        $sub2->create();

        // Max position for subsections should be 1.
        $this->assertEquals(1, $sub2->get_max_position());
    }

    /**
     * Test isrequired field defaults to true on creation.
     */
    public function test_isrequired_defaults_to_true(): void {
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

        // Create an item without specifying isrequired.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
        ]);
        $item->create();

        // Verify isrequired defaults to true.
        $this->assertTrue($item->get('isrequired'));
    }

    /**
     * Test isrequired field can be set to false.
     */
    public function test_isrequired_can_be_false(): void {
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

        // Create an item with isrequired set to false.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Verify isrequired is false.
        $this->assertFalse($item->get('isrequired'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_section_items', ['id' => $item->get('id')]);
        $this->assertEquals(0, $record->isrequired);
    }

    /**
     * Test updating isrequired field.
     */
    public function test_update_isrequired(): void {
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

        // Create an item with isrequired true.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Update isrequired to false.
        $item->set('isrequired', false);
        $item->update();

        // Verify the update.
        $this->assertFalse($item->get('isrequired'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_section_items', ['id' => $item->get('id')]);
        $this->assertEquals(0, $record->isrequired);

        // Update back to true.
        $item->set('isrequired', true);
        $item->update();

        // Verify the update.
        $this->assertTrue($item->get('isrequired'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_section_items', ['id' => $item->get('id')]);
        $this->assertEquals(1, $record->isrequired);
    }

    /**
     * Test querying items by isrequired status.
     */
    public function test_get_items_by_isrequired_status(): void {
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

        // Create required items.
        $requireditem1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $requireditem1->create();

        $requireditem2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $requireditem2->create();

        // Create optional items.
        $optionalitem1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item 1',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => false,
        ]);
        $optionalitem1->create();

        $optionalitem2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item 2',
            'type' => 'textinput',
            'position' => 3,
            'isrequired' => false,
        ]);
        $optionalitem2->create();

        // Query required items.
        $requireditems = $DB->get_records('trainingevaluation_section_items', [
            'sectionid' => $section->get('id'),
            'isrequired' => 1,
        ]);
        $this->assertCount(2, $requireditems);

        // Query optional items.
        $optionalitems = $DB->get_records('trainingevaluation_section_items', [
            'sectionid' => $section->get('id'),
            'isrequired' => 0,
        ]);
        $this->assertCount(2, $optionalitems);
    }
}
