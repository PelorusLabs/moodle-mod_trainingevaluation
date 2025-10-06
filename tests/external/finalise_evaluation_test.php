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

use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the finalise_evaluation class.
 *
 * @package    mod_trainingevaluation
 * @category   test
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_trainingevaluation\external\finalise_evaluation
 */
final class finalise_evaluation_test extends \externallib_advanced_testcase {
    /**
     * Test finalise_evaluation webservice.
     */
    public function test_finalise_evaluation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Finalise evaluation.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertTrue($result);

        // Verify evaluation was finalised.
        $this->assertTrue(evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id])->is_finalised());

        // Verify evaluation record exists.
        $evaluation = evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id]);
        $this->assertNotFalse($evaluation);
        $this->assertTrue($evaluation->get('finalised'));
        $this->assertNotNull($evaluation->get('finalisedby'));
        $this->assertNotNull($evaluation->get('timefinalised'));
    }

    /**
     * Test finalise_evaluation creates evaluation if it doesn't exist.
     */
    public function test_finalise_evaluation_creates_evaluation(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Verify no evaluation exists.
        $this->assertFalse(evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id]));

        // Finalise evaluation.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertTrue($result);

        // Verify evaluation was created and finalised.
        $evaluation = evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id]);
        $this->assertNotFalse($evaluation);
        $this->assertTrue($evaluation->get('finalised'));
    }

    /**
     * Test finalise_evaluation updates existing non-finalised evaluation.
     */
    public function test_finalise_evaluation_updates_existing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create non-finalised evaluation.
        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
            'finalised' => false,
        ]);
        $evaluation->create();
        $evaluationid = $evaluation->get('id');

        // Finalise evaluation.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertTrue($result);

        // Verify same evaluation was updated.
        $evaluation->read();
        $this->assertEquals($evaluationid, $evaluation->get('id'));
        $this->assertTrue($evaluation->get('finalised'));
    }

    /**
     * Test finalise_evaluation returns false for already finalised evaluation.
     */
    public function test_finalise_evaluation_already_finalised(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create and finalise evaluation.
        $evaluation = new evaluation(0, (object)[
            'wtid' => $trainingevaluation->id,
            'userid' => $user->id,
        ]);
        $evaluation->create();
        $evaluation->finalise();

        // Try to finalise again.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertFalse($result);
    }

    /**
     * Test finalise_evaluation with invalid user.
     */
    public function test_finalise_evaluation_invalid_user(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);

        // Try to finalise evaluation for non-existent user.
        $result = finalise_evaluation::execute($trainingevaluation->id, 99999);
        $this->assertFalse($result);
    }

    /**
     * Test finalise_evaluation without evaluate capability.
     */
    public function test_finalise_evaluation_no_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $evaluator = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($evaluator->id, $course->id);

        $this->setUser($evaluator);

        $this->expectException(\moodle_exception::class);
        finalise_evaluation::execute($trainingevaluation->id, $user->id);
    }

    /**
     * Test that finalising locks the evaluation preventing further response changes.
     */
    public function test_finalise_evaluation_locks_responses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section and item.
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Save a response before finalising.
        $result = save_response::execute($item->get('id'), $user->id, 'Initial response');
        $this->assertTrue($result);

        // Verify response was saved.
        $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $user->id]);
        $this->assertEquals('Initial response', $response->get('response'));

        // Finalise the evaluation.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertTrue($result);

        // Try to save a new response after finalising.
        $result = save_response::execute($item->get('id'), $user->id, 'Updated response');
        $this->assertFalse($result);

        // Verify response was not changed.
        $response->read();
        $this->assertEquals('Initial response', $response->get('response'));
    }

    /**
     * Test that evaluation can be finalised even without any responses.
     */
    public function test_finalise_evaluation_without_responses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create a section and item (but don't save any responses).
        $section = new section(0, (object)[
            'wtid' => $trainingevaluation->id,
            'name' => 'Test Section',
            'parentsection' => null,
            'position' => 0,
        ]);
        $section->create();

        $item = new section_item(0, (object)[
            'sectionid' => $section->get('id'),
            'name' => 'Test Item',
            'type' => 'textinput',
            'position' => 0,
            'isrequired' => false,
        ]);
        $item->create();

        // Finalise evaluation without any responses.
        $result = finalise_evaluation::execute($trainingevaluation->id, $user->id);
        $this->assertTrue($result);

        // Verify evaluation is finalised.
        $this->assertTrue(evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id])->is_finalised());
    }
}
