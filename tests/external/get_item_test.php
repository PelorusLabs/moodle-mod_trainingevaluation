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
 * Tests for the get_item class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\get_item
 */
final class get_item_test extends \externallib_advanced_testcase {
    protected function get_item(...$params) {
        $getitem = get_item::execute(...$params);
        return external_api::clean_returnvalue(get_item::execute_returns(), $getitem);
    }

    /**
     * Test get_item webservice.
     */
    public function test_get_item(): void {
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
            'description' => 'Test Description',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Get the item via webservice.
        $result = get_item::execute($item->get('id'));
        $result = \core_external\external_api::clean_returnvalue(get_item::execute_returns(), $result);

        $this->assertEquals($item->get('id'), $result['id']);
        $this->assertEquals('Test Item', $result['name']);
        $this->assertEquals('Test Description', $result['description']);
        $this->assertEquals('textinput', $result['type']);
        $this->assertEquals($section->get('id'), $result['sectionid']);
        $this->assertEquals(0, $result['position']);
        $this->assertEquals(1, $result['isrequired']);
    }

    /**
     * Test get_item with config.
     */
    public function test_get_item_with_config(): void {
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

        // Create an item with config.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save config.
        $typeinstance = $item->get_type_instance();
        $typeinstance->save_config($item, ['maxlength' => '100', 'rows' => 5]);

        // Get the item via webservice.
        $result = get_item::execute($item->get('id'));
        $result = external_api::clean_returnvalue(get_item::execute_returns(), $result);

        $config = json_decode($result['config'], true);
        $this->assertEquals('100', $config['maxlength']);
        $this->assertEquals(5, $config['rows']);
    }
}
