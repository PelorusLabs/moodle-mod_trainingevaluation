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
 * Tests for the delete_item class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\delete_item
 */
final class delete_item_test extends \externallib_advanced_testcase {
    protected function delete_item(...$params) {
        $deleteitem = delete_item::execute(...$params);
        return external_api::clean_returnvalue(delete_item::execute_returns(), $deleteitem);
    }

    /**
     * Test delete_item webservice.
     */
    public function test_delete_item(): void {
        global $DB;

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
            'name' => 'Item to Delete',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();
        $itemid = $item->get('id');

        // Delete the item.
        $result = delete_item::execute($itemid);
        $this->assertTrue($result);

        // Verify item was deleted.
        $this->assertFalse($DB->record_exists('trainingevaluation_section_items', ['id' => $itemid]));
    }

    /**
     * Test delete_item reorders remaining items.
     */
    public function test_delete_item_reorders(): void {
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

        // Create three items.
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

        $item3 = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => false,
        ]);
        $item3->create();

        // Delete middle item.
        delete_item::execute($item2->get('id'));

        // Verify remaining items are reordered.
        $item1->read();
        $item3->read();
        $this->assertEquals(0, $item1->get('position'));
        $this->assertEquals(1, $item3->get('position'));
    }
}
