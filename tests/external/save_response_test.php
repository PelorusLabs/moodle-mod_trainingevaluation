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
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the save_response class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\save_response
 */
final class save_response_test extends \externallib_advanced_testcase {
    protected function save_response(...$params) {
        $saveresponse = save_response::execute(...$params);
        return external_api::clean_returnvalue(save_response::execute_returns(), $saveresponse);
    }

    /**
     * Test save_response webservice.
     */
    public function test_save_response(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Save response.
        $result = save_response::execute($item->get('id'), $user->id, 'Test response data');
        $this->assertTrue($result);

        // Verify response was saved.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertNotFalse($response);
        $this->assertEquals('Test response data', $response->get('response'));
    }

    /**
     * Test save_response updates existing response.
     */
    public function test_save_response_update(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
            'isrequired' => false,
        ]);
        $section->create();

        // Create an item.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save initial response.
        save_response::execute($item->get('id'), $user->id, 'Initial response');

        // Update response.
        $result = save_response::execute($item->get('id'), $user->id, 'Updated response');
        $this->assertTrue($result);

        // Verify response was updated.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertEquals('Updated response', $response->get('response'));

        // Verify only one response exists.
        $responses = response::get_records(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertCount(1, $responses);
    }

    /**
     * Test save_response with empty string.
     */
    public function test_save_response_empty_string(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section.
        $section = new section(0, (object) [
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create an item.
        // Use selectmenu, as textinput will not allow empty string.
        $item = new section_item(0, (object) [
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'selectmenu',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save empty response.
        $result = save_response::execute($item->get('id'), $user->id, '');
        $this->assertTrue($result);

        // Verify empty response was saved.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertNotFalse($response);
        $this->assertEquals('', $response->get('response'));
    }

    /**
     * Test save_response for multiple users.
     */
    public function test_save_response_multiple_users(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Save responses for different users.
        save_response::execute($item->get('id'), $user1->id, 'User 1 response');
        save_response::execute($item->get('id'), $user2->id, 'User 2 response');

        // Verify both responses were saved.
        $response1 = response::get_record(['itemid' => $item->get('id'), 'userid' => $user1->id]);
        $response2 = response::get_record(['itemid' => $item->get('id'), 'userid' => $user2->id]);

        $this->assertNotFalse($response1);
        $this->assertNotFalse($response2);
        $this->assertEquals('User 1 response', $response1->get('response'));
        $this->assertEquals('User 2 response', $response2->get('response'));
    }

    /**
     * Test save_response for multiple items.
     */
    public function test_save_response_multiple_items(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

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

        // Save responses for different items.
        save_response::execute($item1->get('id'), $user->id, 'Response to item 1');
        save_response::execute($item2->get('id'), $user->id, 'Response to item 2');

        // Verify both responses were saved.
        $response1 = response::get_record(['itemid' => $item1->get('id'), 'userid' => $user->id]);
        $response2 = response::get_record(['itemid' => $item2->get('id'), 'userid' => $user->id]);

        $this->assertNotFalse($response1);
        $this->assertNotFalse($response2);
        $this->assertEquals('Response to item 1', $response1->get('response'));
        $this->assertEquals('Response to item 2', $response2->get('response'));
    }

    /**
     * Test save_response with non-existent user.
     */
    public function test_save_response_invalid_user(): void {
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
            'isrequired' => false,
        ]);
        $item->create();

        // Try to save response for non-existent user.
        $result = save_response::execute($item->get('id'), 99999, 'Test response');
        $this->assertFalse($result);
    }

    /**
     * Test save_response without evaluate capability.
     */
    public function test_save_response_no_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $evaluator = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($evaluator->id, $course->id);

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
            'isrequired' => false,
        ]);
        $item->create();

        $this->setUser($evaluator);

        $this->expectException(\moodle_exception::class);
        save_response::execute($item->get('id'), $user->id, 'Test response');
    }

    /**
     * Test save_response with long text.
     */
    public function test_save_response_long_text(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Save long response.
        $longtext = str_repeat('This is a long text response. ', 100);
        $result = save_response::execute($item->get('id'), $user->id, $longtext);
        $this->assertTrue($result);

        // Verify response was saved correctly.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertNotFalse($response);
        $this->assertEquals($longtext, $response->get('response'));
    }

    /**
     * Test save_response fails when evaluation is finalised.
     */
    public function test_save_response_finalised_evaluation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Create and finalise evaluation.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);
        $evaluation->finalise();

        // Try to save response after finalisation.
        $result = save_response::execute($item->get('id'), $user->id, 'Test response');
        $this->assertFalse($result);

        // Verify no response was created.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertFalse($response);
    }

    /**
     * Test save_response cannot update existing response when evaluation is finalised.
     */
    public function test_save_response_cannot_update_when_finalised(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Save initial response.
        $result = save_response::execute($item->get('id'), $user->id, 'Initial response');
        $this->assertTrue($result);

        // Verify initial response was saved.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertEquals('Initial response', $response->get('response'));

        // Finalise evaluation.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);
        $evaluation->finalise();

        // Try to update response after finalisation.
        $result = save_response::execute($item->get('id'), $user->id, 'Updated response');
        $this->assertFalse($result);

        // Verify response was not updated.
        $response->read();
        $this->assertEquals('Initial response', $response->get('response'));
    }

    /**
     * Test save_response works for different users even when one evaluation is finalised.
     */
    public function test_save_response_other_user_unaffected_by_finalisation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

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
            'isrequired' => false,
        ]);
        $item->create();

        // Finalise evaluation for user1.
        $evaluation1 = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user1->id);
        $evaluation1->finalise();

        // User1 cannot save response.
        $result = save_response::execute($item->get('id'), $user1->id, 'User 1 response');
        $this->assertFalse($result);

        // User2 can still save response.
        $result = save_response::execute($item->get('id'), $user2->id, 'User 2 response');
        $this->assertTrue($result);

        // Verify user2's response was saved.
        $response2 = response::get_record(['itemid' => $item->get('id'), 'userid' => $user2->id]);
        $this->assertNotFalse($response2);
        $this->assertEquals('User 2 response', $response2->get('response'));
    }
}
