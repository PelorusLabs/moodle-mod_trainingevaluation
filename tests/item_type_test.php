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

use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;
use mod_trainingevaluation\local\types\type_datepicker;
use mod_trainingevaluation\local\types\type_textinput;

/**
 * Unit tests for training evaluation item types.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\local\types\base
 * @covers \mod_trainingevaluation\local\types\type_textinput
 */
final class item_type_test extends \advanced_testcase {
    /**
     * Test getting item type instance.
     */
    public function test_get_type_instance(): void {
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

        // Create a textinput item.
        $textinputitem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $textinputitem->create();

        // Get type instance.
        $typeinstance = $textinputitem->get_type_instance();
        $this->assertInstanceOf(type_textinput::class, $typeinstance);

        // Create a datepicker item.
        $datepickeritem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Display Text Item',
            'type' => 'datepicker',
            'position' => 1,
            'isrequired' => false,
        ]);
        $datepickeritem->create();

        // Get type instance.
        $typeinstance = $datepickeritem->get_type_instance();
        $this->assertInstanceOf(type_datepicker::class, $typeinstance);
    }

    /**
     * Test getting non-existent item type instance.
     */
    public function test_get_nonexistent_type_instance(): void {
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

        // Create an item with non-existent type.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Unknown Type Item',
            'type' => 'nonexistent',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Get type instance should return null.
        $typeinstance = $item->get_type_instance();
        $this->assertNull($typeinstance);
    }

    /**
     * Test textinput type save response.
     */
    public function test_textinput_save_response(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create a textinput item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation = $evaluation->create();

        // Save response.
        $type = new type_textinput();
        $result = $type->save_response($cm, $trainingevaluation, $item, $evaluation, 'Test response text');
        $this->assertTrue($result);

        // Verify response was saved.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertNotFalse($response);
        $this->assertEquals('Test response text', $response->get('response'));
    }

    /**
     * Test textinput type update response.
     */
    public function test_textinput_update_response(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create a textinput item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation = $evaluation->create();

        // Save initial response.
        $type = new type_textinput();
        $type->save_response($cm, $trainingevaluation, $item, $evaluation, 'Initial response');

        // Update response.
        $type->save_response($cm, $trainingevaluation, $item, $evaluation, 'Updated response');

        // Verify response was updated.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertNotFalse($response);
        $this->assertEquals('Updated response', $response->get('response'));
    }

    /**
     * Test textinput type get response.
     */
    public function test_textinput_get_response(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create a textinput item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation = $evaluation->create();

        // Save response.
        $type = new type_textinput();
        $type->save_response($cm, $trainingevaluation, $item, $evaluation, 'Saved response');

        // Get response.
        $response = $type->get_response($item->get('id'), $user->id);
        $this->assertInstanceOf(response::class, $response);
        $this->assertEquals('Saved response', $response->get('response'));
    }

    /**
     * Test get response for non-existent response.
     */
    public function test_get_nonexistent_response(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create a textinput item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Get response that doesn't exist.
        $type = new type_textinput();
        $response = $type->get_response($item->get('id'), $user->id);
        $this->assertFalse($response);
    }

    /**
     * Test save and get config.
     */
    public function test_save_and_get_config(): void {
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
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save config.
        $type = new type_textinput();
        $config = [
            'maxlength' => 100,
            'placeholdertext' => 'Enter text here',
            'rows' => 5,
        ];
        $type->save_config($item, $config);

        // Verify config was saved to database.
        $configrecords = $DB->get_records('trainingevaluation_item_config', ['itemid' => $item->get('id')]);
        $this->assertCount(3, $configrecords);

        // Get config.
        $retrievedconfig = $type->get_config($item);
        $this->assertEquals(100, $retrievedconfig['maxlength']);
        $this->assertEquals('Enter text here', $retrievedconfig['placeholdertext']);
        $this->assertEquals(5, $retrievedconfig['rows']);
    }

    /**
     * Test update config.
     */
    public function test_update_config(): void {
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
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save initial config.
        $type = new type_textinput();
        $config = ['maxlength' => 100, 'rows' => 4];
        $type->save_config($item, $config);

        // Update config with new values.
        $newconfig = ['maxlength' => 200, 'placeholdertext' => 'New placeholder', 'rows' => 5];
        $type->save_config($item, $newconfig);

        // Get config and verify old values are replaced.
        $retrievedconfig = $type->get_config($item);
        $this->assertEquals(200, $retrievedconfig['maxlength']);
        $this->assertEquals('New placeholder', $retrievedconfig['placeholdertext']);
        $this->assertEquals(5, $retrievedconfig['rows']);
        $this->assertArrayNotHasKey('exampleinput', $retrievedconfig);
    }

    /**
     * Test textinput render methods return non-empty strings.
     */
    public function test_textinput_render_methods(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create a textinput item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Text Input Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation = $evaluation->create();

        $type = new type_textinput();

        // Test render_manage_form.
        $managehtml = $type->render_manage_form($trainingevaluation, $item, $user->id);
        $this->assertNotEmpty($managehtml);
        $this->assertStringContainsString('textarea', $managehtml);

        // Test render_user_form.
        $userhtml = $type->render_user_form($trainingevaluation, $item, $evaluation);
        $this->assertNotEmpty($userhtml);
        $this->assertStringContainsString('textarea', $userhtml);

        // Test render_evaluate_form.
        $evaluatehtml = $type->render_evaluate_form($trainingevaluation, $item, $evaluation);
        $this->assertNotEmpty($evaluatehtml);
        $this->assertStringContainsString('textarea', $evaluatehtml);
    }

    /**
     * Test empty config returns empty array.
     */
    public function test_empty_config(): void {
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

        // Create an item without config.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Get config should return empty array.
        $type = new type_textinput();
        $config = $type->get_config($item);
        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }
}
