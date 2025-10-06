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

/**
 * Unit tests for training evaluations.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_trainingevaluation\local\evaluation
 */
final class evaluation_test extends \advanced_testcase {
    /**
     * Test evaluation creation.
     */
    public function test_evaluation_creation(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create an evaluation.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);

        // Verify evaluation was created.
        $this->assertTrue($evaluation->get('id') > 0);
        $this->assertEquals($trainingevaluation->id, $evaluation->get('wtid'));
        $this->assertEquals($user->id, $evaluation->get('userid'));
        $this->assertFalse($evaluation->get('finalised'));
        $this->assertNull($evaluation->get('finalisedby'));
        $this->assertNull($evaluation->get('timefinalised'));

        // Verify database record.
        $record = $DB->get_record('trainingevaluation_evaluations', ['id' => $evaluation->get('id')]);
        $this->assertNotFalse($record);
        $this->assertEquals(0, $record->finalised);
    }

    /**
     * Test evaluation finalisation.
     */
    public function test_evaluation_finalisation(): void {
        global $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create an evaluation.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);

        // Record time before finalisation.
        $timebefore = time();

        // Finalise the evaluation.
        $result = $evaluation->finalise();
        $this->assertTrue($result);

        // Verify evaluation was finalised.
        $evaluation->read();
        $this->assertTrue($evaluation->get('finalised'));
        $this->assertEquals($USER->id, $evaluation->get('finalisedby'));
        $this->assertGreaterThanOrEqual($timebefore, $evaluation->get('timefinalised'));
        $this->assertLessThanOrEqual(time(), $evaluation->get('timefinalised'));

        // Verify database record.
        $record = $DB->get_record('trainingevaluation_evaluations', ['id' => $evaluation->get('id')]);
        $this->assertEquals(1, $record->finalised);
        $this->assertEquals($USER->id, $record->finalisedby);
        $this->assertNotNull($record->timefinalised);
    }

    /**
     * Test multiple users can have separate evaluations.
     */
    public function test_multiple_user_evaluations(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create evaluations for both users.
        $evaluation1 = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user1->id);

        $evaluation2 = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user2->id);

        // Finalise only user1's evaluation.
        $evaluation1->finalise();

        // Verify user1's evaluation is finalised.
        $this->assertTrue(evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user1->id])->is_finalised());

        // Verify user2's evaluation is not finalised.
        $this->assertFalse(evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user2->id])->is_finalised());
    }

    /**
     * Test evaluation retrieval by wtid and userid.
     */
    public function test_get_evaluation_by_wtid_and_userid(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create an evaluation.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);

        // Retrieve evaluation by wtid and userid.
        $retrieved = evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $user->id]);

        // Verify correct evaluation retrieved.
        $this->assertNotFalse($retrieved);
        $this->assertEquals($evaluation->get('id'), $retrieved->get('id'));
    }

    /**
     * Test finalised evaluation defaults are set correctly.
     */
    public function test_evaluation_defaults(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();

        // Create an evaluation without specifying finalised fields.
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $user->id);

        // Verify defaults.
        $this->assertFalse($evaluation->get('finalised'));
        $this->assertNull($evaluation->get('finalisedby'));
        $this->assertNull($evaluation->get('timefinalised'));
        $this->assertEquals(1, $evaluation->get('version'));
    }
}
