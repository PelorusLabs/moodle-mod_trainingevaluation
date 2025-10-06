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

/**
 * Unit tests for training evaluation responses.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\local\response
 */
final class response_test extends \advanced_testcase {
    /**
     * Test response creation.
     */
    public function test_response_creation(): void {
        global $DB;

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

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create a response.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Test response data',
        ]);
        $response->create();

        // Verify response was created.
        $this->assertTrue($response->get('id') > 0);
        $this->assertEquals($item->get('id'), $response->get('itemid'));
        $this->assertEquals($user->id, $response->get('userid'));
        $this->assertEquals('Test response data', $response->get('response'));

        // Verify database record.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $response->get('id')]);
        $this->assertNotFalse($record);
        $this->assertEquals('Test response data', $record->response);
    }

    /**
     * Test response update.
     */
    public function test_response_update(): void {
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

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create a response.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Initial response',
        ]);
        $response->create();

        // Update the response.
        $response->set('response', 'Updated response');
        $response->update();

        // Verify response was updated.
        $response->read();
        $this->assertEquals('Updated response', $response->get('response'));
    }

    /**
     * Test response deletion.
     */
    public function test_response_deletion(): void {
        global $DB;

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

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create a response.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Test response',
        ]);
        $response->create();
        $responseid = $response->get('id');

        // Delete the response.
        $result = $response->delete();
        $this->assertTrue($result);

        // Verify response was deleted.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $responseid]);
        $this->assertFalse($record);
    }

    /**
     * Test empty string response data.
     */
    public function test_empty_string_response_data(): void {
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

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create a response with empty string response data.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => '',
        ]);
        $response->create();

        // Verify empty string response is allowed.
        $this->assertTrue($response->get('id') > 0);
        $this->assertEquals('', $response->get('response'));
    }

    /**
     * Test multiple responses from different users.
     */
    public function test_multiple_user_responses(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

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
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create responses from different users.
        $response1 = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user1->id,
            'response' => 'User 1 response',
        ]);
        $response1->create();

        $response2 = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user2->id,
            'response' => 'User 2 response',
        ]);
        $response2->create();

        // Verify both responses exist with correct data.
        $this->assertEquals('User 1 response', $response1->get('response'));
        $this->assertEquals('User 2 response', $response2->get('response'));

        // Verify they are different records.
        $this->assertNotEquals($response1->get('id'), $response2->get('id'));
    }

    /**
     * Test response retrieval by item and user.
     */
    public function test_get_response_by_item_and_user(): void {
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

        // Create an item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Create a response.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Retrieved response',
        ]);
        $response->create();

        // Retrieve response by item and user.
        $retrieved = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);

        // Verify correct response retrieved.
        $this->assertNotFalse($retrieved);
        $this->assertEquals($response->get('id'), $retrieved->get('id'));
        $this->assertEquals('Retrieved response', $retrieved->get('response'));
    }

    /**
     * Test responses for multiple items.
     */
    public function test_responses_for_multiple_items(): void {
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

        // Create responses for both items.
        $response1 = new response(0, (object)[
            'itemid' => $item1->get('id'),
            'userid' => $user->id,
            'response' => 'Response to item 1',
        ]);
        $response1->create();

        $response2 = new response(0, (object)[
            'itemid' => $item2->get('id'),
            'userid' => $user->id,
            'response' => 'Response to item 2',
        ]);
        $response2->create();

        // Verify responses are associated with correct items.
        $this->assertEquals($item1->get('id'), $response1->get('itemid'));
        $this->assertEquals($item2->get('id'), $response2->get('itemid'));
        $this->assertEquals('Response to item 1', $response1->get('response'));
        $this->assertEquals('Response to item 2', $response2->get('response'));
    }

    /**
     * Test completed field defaults to false on creation.
     */
    public function test_completed_defaults_to_false(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
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
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Create a response without specifying completed.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Test response',
        ]);
        $response->create();

        // Verify completed defaults to false.
        $this->assertFalse($response->get('completed'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $response->get('id')]);
        $this->assertEquals(0, $record->completed);
    }

    /**
     * Test completed field can be set to true.
     */
    public function test_completed_can_be_true(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
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
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Create a response with completed set to true.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Test response',
            'completed' => true,
        ]);
        $response->create();

        // Verify completed is true.
        $this->assertTrue($response->get('completed'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $response->get('id')]);
        $this->assertEquals(1, $record->completed);
    }

    /**
     * Test updating completed field.
     */
    public function test_update_completed(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
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
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        // Create a response with completed false.
        $response = new response(0, (object)[
            'itemid' => $item->get('id'),
            'userid' => $user->id,
            'response' => 'Test response',
            'completed' => false,
        ]);
        $response->create();

        // Update completed to true.
        $response->set('completed', true);
        $response->update();

        // Verify the update.
        $this->assertTrue($response->get('completed'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $response->get('id')]);
        $this->assertEquals(1, $record->completed);

        // Update back to false.
        $response->set('completed', false);
        $response->update();

        // Verify the update.
        $this->assertFalse($response->get('completed'));

        // Verify in database.
        $record = $DB->get_record('trainingevaluation_responses', ['id' => $response->get('id')]);
        $this->assertEquals(0, $record->completed);
    }

    /**
     * Test querying responses by completed status.
     */
    public function test_get_responses_by_completed_status(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
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
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
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

        // Create completed responses.
        $completedresponse1 = new response(0, (object)[
            'itemid' => $item1->get('id'),
            'userid' => $user->id,
            'response' => 'Completed response 1',
            'completed' => true,
        ]);
        $completedresponse1->create();

        $completedresponse2 = new response(0, (object)[
            'itemid' => $item2->get('id'),
            'userid' => $user->id,
            'response' => 'Completed response 2',
            'completed' => true,
        ]);
        $completedresponse2->create();

        // Create incomplete response.
        $incompleteresponse = new response(0, (object)[
            'itemid' => $item3->get('id'),
            'userid' => $user->id,
            'response' => 'Incomplete response',
            'completed' => false,
        ]);
        $incompleteresponse->create();

        // Query completed responses.
        $completedresponses = $DB->get_records('trainingevaluation_responses', [
            'userid' => $user->id,
            'completed' => 1,
        ]);
        $this->assertCount(2, $completedresponses);

        // Query incomplete responses.
        $incompleteresponses = $DB->get_records('trainingevaluation_responses', [
            'userid' => $user->id,
            'completed' => 0,
        ]);
        $this->assertCount(1, $incompleteresponses);
    }

    /**
     * Test completed status is updated automatically on save_response.
     */
    public function test_completed_status_auto_updated_on_save(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $trainingevaluation->course);

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
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation = $evaluation->create();

        // Get type instance and save response.
        $typeinstance = $item->get_type_instance();
        $typeinstance->save_response($cm, $trainingevaluation, $item, $evaluation, 'Test response data');

        // Get the response.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);

        // Verify completed status was set based on has_user_completed.
        $this->assertNotNull($response);
        $expected = $typeinstance->has_user_completed($item->get('id'), $user->id, $evaluation->get('version'));
        $this->assertEquals($expected, $response->get('completed'));
    }

    /**
     * Test filtering user progress based on required items and completed responses.
     */
    public function test_calculate_user_completion_progress(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create 3 required items and 1 optional item.
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

        $requireditem3 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => true,
        ]);
        $requireditem3->create();

        $optionalitem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item',
            'type' => 'textinput',
            'position' => 3,
            'isrequired' => false,
        ]);
        $optionalitem->create();

        // User completes 2 required items and the optional item.
        $response1 = new response(0, (object)[
            'itemid' => $requireditem1->get('id'),
            'userid' => $user->id,
            'response' => 'Response 1',
            'completed' => true,
        ]);
        $response1->create();

        $response2 = new response(0, (object)[
            'itemid' => $requireditem2->get('id'),
            'userid' => $user->id,
            'response' => 'Response 2',
            'completed' => true,
        ]);
        $response2->create();

        $response3 = new response(0, (object)[
            'itemid' => $optionalitem->get('id'),
            'userid' => $user->id,
            'response' => 'Optional Response',
            'completed' => true,
        ]);
        $response3->create();

        // Calculate completion: 2 out of 3 required items completed.
        $sql = "SELECT COUNT(DISTINCT si.id) as total
                FROM {trainingevaluation_section_items} si
                WHERE si.sectionid = :sectionid AND si.isrequired = 1";
        $totalrequired = $DB->get_field_sql($sql, ['sectionid' => $section->get('id')]);

        $sql = "SELECT COUNT(DISTINCT r.itemid) as completed
                FROM {trainingevaluation_responses} r
                JOIN {trainingevaluation_section_items} si ON si.id = r.itemid
                WHERE si.sectionid = :sectionid
                AND r.userid = :userid
                AND si.isrequired = 1
                AND r.completed = 1";
        $completedrequired = $DB->get_field_sql($sql, ['sectionid' => $section->get('id'), 'userid' => $user->id]);

        // Assert completion status.
        $this->assertEquals(3, $totalrequired);
        $this->assertEquals(2, $completedrequired);

        // User is not complete (needs 3 of 3 required).
        $this->assertLessThan($totalrequired, $completedrequired);
    }
}
