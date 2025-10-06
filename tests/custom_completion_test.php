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

use mod_trainingevaluation\completion\custom_completion;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

/**
 * Unit tests for training evaluation custom completion.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\completion\custom_completion
 */
final class custom_completion_test extends \advanced_testcase {
    /**
     * Test completion with no required items returns complete.
     */
    public function test_completion_no_required_items(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Create training evaluation with completion enabled.
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);
        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section with only optional items.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create an optional item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Check completion status - should be complete with no required items.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_COMPLETE, $state);
    }

    /**
     * Test completion with required items not completed returns incomplete.
     */
    public function test_completion_required_items_not_completed(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create required items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $item2->create();

        // Check completion status - should be incomplete.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_INCOMPLETE, $state);
    }

    /**
     * Test completion with all required items completed returns complete.
     */
    public function test_completion_all_required_items_completed(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create required items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $item2->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();

        // Complete both required items using save_response.
        $item1->get_type_instance()->save_response($cm, $trainingevaluation, $item1, $evaluation, 'Response 1');
        $item2->get_type_instance()->save_response($cm, $trainingevaluation, $item2, $evaluation, 'Response 2');

        // Check completion status - should be complete.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_COMPLETE, $state);
    }

    /**
     * Test completion with some required items completed returns incomplete.
     */
    public function test_completion_partial_required_items_completed(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create three required items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $item2->create();

        $item3 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 3',
            'type' => 'textinput',
            'position' => 2,
            'isrequired' => true,
        ]);
        $item3->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();

        // Complete only two of three required items using save_response.
        $item1->get_type_instance()->save_response($cm, $trainingevaluation, $item1, $evaluation, 'Response 1');
        $item2->get_type_instance()->save_response($cm, $trainingevaluation, $item2, $evaluation, 'Response 2');

        // Check completion status - should be incomplete.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_INCOMPLETE, $state);
    }

    /**
     * Test completion ignores optional items.
     */
    public function test_completion_ignores_optional_items(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create required item.
        $requireditem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $requireditem->create();

        // Create optional item.
        $optionalitem = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Optional Item',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => false,
        ]);
        $optionalitem->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();

        // Complete only the required item using save_response.
        $requireditem->get_type_instance()->save_response($cm, $trainingevaluation, $requireditem, $evaluation, 'Response');

        // Check completion status - should be complete (optional item not needed).
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_COMPLETE, $state);
    }

    /**
     * Test completion with incomplete responses returns incomplete.
     */
    public function test_completion_with_incomplete_responses(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create required items.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 1',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item 2',
            'type' => 'textinput',
            'position' => 1,
            'isrequired' => true,
        ]);
        $item2->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();

        // Don't save any responses - items remain incomplete.

        // Check completion status - should be incomplete.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_INCOMPLETE, $state);
    }

    /**
     * Test completion across multiple sections.
     */
    public function test_completion_multiple_sections(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create two sections.
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

        // Create required items in both sections.
        $item1 = new section_item(0, (object)[
            'sectionid' => $section1->get('id'),
            'name' => 'Section 1 Required Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item1->create();

        $item2 = new section_item(0, (object)[
            'sectionid' => $section2->get('id'),
            'name' => 'Section 2 Required Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item2->create();

        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();

        // Complete items from both sections using save_response.
        $item1->get_type_instance()->save_response($cm, $trainingevaluation, $item1, $evaluation, 'Response 1');
        $item2->get_type_instance()->save_response($cm, $trainingevaluation, $item2, $evaluation, 'Response 2');

        // Check completion status - should be complete.
        $completion = new custom_completion($cm, $user->id);
        $state = $completion->get_state('completiononrequired');

        $this->assertEquals(COMPLETION_COMPLETE, $state);
    }

    /**
     * Test completion for different users independently.
     */
    public function test_completion_different_users(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiononrequired' => 1,
        ]);

        [$course, $cm] = get_course_and_cm_from_instance($trainingevaluation, 'trainingevaluation');

        // Create a section.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        // Create required item.
        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Required Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => true,
        ]);
        $item->create();

        $evaluation1 = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user1->id,
        ]);
        $evaluation1->create();

        $evaluation2 = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user2->id,
        ]);
        $evaluation2->create();

        // User 1 completes the item using save_response.
        $item->get_type_instance()->save_response($cm, $trainingevaluation, $item, $evaluation1, 'User 1 Response');

        // Check user 1 completion - should be complete.
        $completion1 = new custom_completion($cm, $user1->id);
        $state1 = $completion1->get_state('completiononrequired');
        $this->assertEquals(COMPLETION_COMPLETE, $state1);

        // Check user 2 completion - should be incomplete.
        $completion2 = new custom_completion($cm, $user2->id);
        $state2 = $completion2->get_state('completiononrequired');
        $this->assertEquals(COMPLETION_INCOMPLETE, $state2);
    }
}
