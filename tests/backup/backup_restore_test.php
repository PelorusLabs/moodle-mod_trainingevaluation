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

use backup;
use backup_controller;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Unit tests for training evaluation backup and restore.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_trainingevaluation_activity_task
 * @covers \backup_trainingevaluation_activity_structure_step
 * @covers \restore_trainingevaluation_activity_task
 * @covers \restore_trainingevaluation_activity_structure_step
 */
final class backup_restore_test extends \advanced_testcase {
    private function create_section(int $wtid, string $name, ?int $parentsection, int $position): int {
        $section = new section(0, (object)[
            'wtid' => $wtid,
            'name' => $name,
            'parentsection' => $parentsection,
            'position' => $position,
        ]);
        $section->create();
        return $section->get('id');
    }
    private function create_section_item(int $sectionid, string $name, string $description, string $type, int $position, bool $isrequired): int {
        $item = new section_item(0, (object)[
            'sectionid' => $sectionid,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'position' => $position,
            'isrequired' => $isrequired,
        ]);
        $item->create();
        return $item->get('id');
    }
    private function create_response(int $itemid, int $userid, string $response, bool $completed) {
        $response = new response(0, (object)[
            'itemid' => $itemid,
            'userid' => $userid,
            'response' => $response,
            'completed' => $completed,
        ]);
        $response->create();
        return $response->get('id');
    }
    /**
     * Test basic backup and restore without user data.
     */
    public function test_backup_restore_basic(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'name' => 'Original Training',
            'intro' => 'Original description',
            'showlastmodified' => 1,
        ]);

        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored activity.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $this->assertCount(1, $modules);

        $newmodule = reset($modules);
        $restoredwt = $DB->get_record('trainingevaluation', ['id' => $newmodule->instance]);

        $this->assertEquals('Original Training', $restoredwt->name);
        $this->assertEquals('Original description', $restoredwt->intro);
        $this->assertEquals(1, $restoredwt->showlastmodified);
    }

    /**
     * Test backup and restore with sections.
     */
    public function test_backup_restore_with_sections(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'name' => 'Training with Sections',
        ]);

        // Create sections.
        $section1 = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        $this->create_section($trainingevaluation->id, 'Section 2', $section1, 1);

        // Perform backup.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored sections.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance], 'position ASC');
        $this->assertCount(2, $sections);

        $sectionarray = array_values($sections);
        $this->assertEquals('Section 1', $sectionarray[0]->name);
        $this->assertNull($sectionarray[0]->parentsection);
        $this->assertEquals(0, $sectionarray[0]->position);

        $this->assertEquals('Section 2', $sectionarray[1]->name);
        $this->assertEquals($sectionarray[0]->id, $sectionarray[1]->parentsection);
        $this->assertEquals(1, $sectionarray[1]->position);
    }

    /**
     * Test backup and restore with section items.
     */
    public function test_backup_restore_with_items(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        // Create section.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        // Create items.
        $this->create_section_item($sectionid, 'Item 1', 'Description 1', 'textinput', 0, false);
        $this->create_section_item($sectionid, 'Item 2', 'Description 2', 'selectmenu', 1, false);

        // Perform backup.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored items.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id], 'position ASC');
        $this->assertCount(2, $items);

        $itemarray = array_values($items);
        $this->assertEquals('Item 1', $itemarray[0]->name);
        $this->assertEquals('Description 1', $itemarray[0]->description);
        $this->assertEquals('textinput', $itemarray[0]->type);
        $this->assertEquals(0, $itemarray[0]->position);

        $this->assertEquals('Item 2', $itemarray[1]->name);
        $this->assertEquals('Description 2', $itemarray[1]->description);
        $this->assertEquals('selectmenu', $itemarray[1]->type);
        $this->assertEquals(1, $itemarray[1]->position);
    }

    /**
     * Test backup and restore with item configs.
     */
    public function test_backup_restore_with_item_configs(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        // Create section.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        // Create item.
        $itemid = $this->create_section_item($sectionid, 'Configured Item', 'Item with config', 'selectmenu', 0, false);

        // Create configs.
        $DB->insert_record('trainingevaluation_item_config', (object) [
            'itemid' => $itemid,
            'name' => 'option1',
            'value' => 'Option 1',
        ]);

        $DB->insert_record('trainingevaluation_item_config', (object) [
            'itemid' => $itemid,
            'name' => 'option2',
            'value' => 'Option 2',
        ]);

        // Perform backup.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored configs.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id]);
        $item = reset($items);

        $configs = $DB->get_records('trainingevaluation_item_config', ['itemid' => $item->id], 'name ASC');
        $this->assertCount(2, $configs);

        $configarray = array_values($configs);
        $this->assertEquals('option1', $configarray[0]->name);
        $this->assertEquals('Option 1', $configarray[0]->value);
        $this->assertEquals('option2', $configarray[1]->name);
        $this->assertEquals('Option 2', $configarray[1]->value);
    }

    /**
     * Test backup and restore with user responses (userinfo enabled).
     */
    public function test_backup_restore_with_responses(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, users and activity.
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        // Create section and item.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        $itemid = $this->create_section_item($sectionid, 'Item with responses', 'Item description', 'textinput', 0, false);

        // Create responses.
        $this->create_response($itemid, $student1->id, 'Student 1 response', true);
        $this->create_response($itemid, $student2->id, 'Student 2 response', true);

        // Perform backup WITH users.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored responses.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id]);
        $item = reset($items);

        $responses = $DB->get_records('trainingevaluation_responses', ['itemid' => $item->id], 'userid ASC');
        $this->assertCount(2, $responses);

        $responsearray = array_values($responses);
        $this->assertEquals($student1->id, $responsearray[0]->userid);
        $this->assertEquals('Student 1 response', $responsearray[0]->response);

        $this->assertEquals($student2->id, $responsearray[1]->userid);
        $this->assertEquals('Student 2 response', $responsearray[1]->response);
    }

    /**
     * Test backup and restore without user responses (userinfo disabled).
     */
    public function test_backup_restore_without_responses(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, users and activity.
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        // Create section and item.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);
        $itemid = $this->create_section_item($sectionid, 'Item with responses', 'Description 1', 'textinput', 0, false);

        // Create responses.
        $this->create_response($itemid, $student->id, 'Student response', true);

        // Perform backup WITHOUT users.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(false);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify no responses were restored.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id]);
        $item = reset($items);

        $responses = $DB->get_records('trainingevaluation_responses', ['itemid' => $item->id]);
        $this->assertCount(0, $responses);
    }

    /**
     * Test backup and restore with fileupload items and files.
     */
    public function test_backup_restore_with_fileupload_files(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, users and activity.
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id);
        $context = \context_module::instance($cm->id);

        // Create section and fileupload item.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        $itemid = $this->create_section_item($sectionid, 'File Upload Item', 'Upload your files here', 'fileupload', 0, false);

        // Create dynamic filearea name based on item id.
        $filearea = 'type_fileupload_' . $itemid;

        // Create test files for both students.
        $file1 = $this->create_test_file(
            $context->id,
            'mod_trainingevaluation',
            $filearea,
            $student1->id,
            'student1_file.txt',
            'Student 1 file content'
        );

        $file2 = $this->create_test_file(
            $context->id,
            'mod_trainingevaluation',
            $filearea,
            $student2->id,
            'student2_file.pdf',
            'Student 2 file content'
        );

        // Verify files were created.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_trainingevaluation', $filearea, false, '', false);
        $this->assertCount(2, $files);

        // Perform backup WITH users.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored files.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);
        $newcm = get_coursemodule_from_id('trainingevaluation', $newmodule->id);
        $newcontext = \context_module::instance($newcm->id);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id]);
        $item = reset($items);

        // The new filearea should be based on the NEW item id.
        $newfilearea = 'type_fileupload_' . $item->id;

        // Check that files were restored to the correct filearea.
        $restoredfiles = $fs->get_area_files($newcontext->id, 'mod_trainingevaluation', $newfilearea, false, '', false);
        $this->assertCount(2, $restoredfiles, 'Should have restored 2 files');

        // Verify file contents and itemids (which should be student user IDs).
        $restoredfilesarray = array_values($restoredfiles);

        $foundstudent1 = false;
        $foundstudent2 = false;

        foreach ($restoredfilesarray as $restoredfile) {
            if ($restoredfile->get_itemid() == $student1->id && $restoredfile->get_filename() == 'student1_file.txt') {
                $this->assertEquals('Student 1 file content', $restoredfile->get_content());
                $foundstudent1 = true;
            }
            if ($restoredfile->get_itemid() == $student2->id && $restoredfile->get_filename() == 'student2_file.pdf') {
                $this->assertEquals('Student 2 file content', $restoredfile->get_content());
                $foundstudent2 = true;
            }
        }

        $this->assertTrue($foundstudent1, 'Student 1 file should be restored');
        $this->assertTrue($foundstudent2, 'Student 2 file should be restored');
    }

    /**
     * Test backup and restore with multiple fileupload items to ensure filearea mapping works correctly.
     */
    public function test_backup_restore_with_multiple_fileupload_items(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course, user and activity.
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();

        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
        ]);

        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id);
        $context = \context_module::instance($cm->id);

        // Create section.
        $sectionid = $this->create_section($trainingevaluation->id, 'Section 1', null, 0);

        // Create two fileupload items.
        $item1id = $this->create_section_item($sectionid, 'File Upload Item 1', 'Upload files 1', 'fileupload', 0, false);
        $item2id = $this->create_section_item($sectionid, 'File Upload Item 2', 'Upload files 2', 'fileupload', 1, false);

        // Create files for each item.
        $filearea1 = 'type_fileupload_' . $item1id;
        $filearea2 = 'type_fileupload_' . $item2id;

        $this->create_test_file(
            $context->id,
            'mod_trainingevaluation',
            $filearea1,
            $student->id,
            'item1_file.txt',
            'Item 1 content'
        );

        $this->create_test_file(
            $context->id,
            'mod_trainingevaluation',
            $filearea2,
            $student->id,
            'item2_file.txt',
            'Item 2 content'
        );

        // Perform backup WITH users.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the restored files are in correct fileareas.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);
        $newcm = get_coursemodule_from_id('trainingevaluation', $newmodule->id);
        $newcontext = \context_module::instance($newcm->id);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance]);
        $section = reset($sections);

        $items = $DB->get_records('trainingevaluation_section_items', ['sectionid' => $section->id], 'position ASC');
        $this->assertCount(2, $items);

        $itemsarray = array_values($items);
        $newitem1 = $itemsarray[0];
        $newitem2 = $itemsarray[1];

        $fs = get_file_storage();

        // Check files for item 1.
        $newfilearea1 = 'type_fileupload_' . $newitem1->id;
        $files1 = $fs->get_area_files($newcontext->id, 'mod_trainingevaluation', $newfilearea1, false, '', false);
        $this->assertCount(1, $files1);
        $file1 = reset($files1);
        $this->assertEquals('item1_file.txt', $file1->get_filename());
        $this->assertEquals('Item 1 content', $file1->get_content());

        // Check files for item 2.
        $newfilearea2 = 'type_fileupload_' . $newitem2->id;
        $files2 = $fs->get_area_files($newcontext->id, 'mod_trainingevaluation', $newfilearea2, false, '', false);
        $this->assertCount(1, $files2);
        $file2 = reset($files2);
        $this->assertEquals('item2_file.txt', $file2->get_filename());
        $this->assertEquals('Item 2 content', $file2->get_content());
    }

    /**
     * Test complex hierarchical structure backup and restore.
     */
    public function test_backup_restore_complex_hierarchy(): void {
        global $DB, $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and activity.
        $course = $this->getDataGenerator()->create_course();
        $trainingevaluation = $this->getDataGenerator()->create_module('trainingevaluation', [
            'course' => $course->id,
            'name' => 'Complex Training',
        ]);

        // Create parent section.
        $parentsection = $this->create_section($trainingevaluation->id, 'Parent Section', null, 0);

        // Create child section 1.
        $childsection1 = $this->create_section($trainingevaluation->id, 'Child Section 1', $parentsection, 1);

        // Create child section 2.
        $this->create_section($trainingevaluation->id, 'Child Section 2', $parentsection, 2);

        // Create grandchild section.
        $this->create_section($trainingevaluation->id, 'Grandchild Section', $childsection1, 3);

        // Perform backup.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $trainingevaluation->cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/' . $backupid;
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Create new course for restore.
        $newcourse = $this->getDataGenerator()->create_course();

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Verify the hierarchy is preserved.
        $modules = get_coursemodules_in_course('trainingevaluation', $newcourse->id);
        $newmodule = reset($modules);

        $sections = $DB->get_records('trainingevaluation_sections', ['wtid' => $newmodule->instance], 'position ASC');
        $this->assertCount(4, $sections);

        $sectionarray = array_values($sections);

        // Parent section.
        $this->assertEquals('Parent Section', $sectionarray[0]->name);
        $this->assertNull($sectionarray[0]->parentsection);

        // Child section 1.
        $this->assertEquals('Child Section 1', $sectionarray[1]->name);
        $this->assertEquals($sectionarray[0]->id, $sectionarray[1]->parentsection);

        // Child section 2.
        $this->assertEquals('Child Section 2', $sectionarray[2]->name);
        $this->assertEquals($sectionarray[0]->id, $sectionarray[2]->parentsection);

        // Grandchild section.
        $this->assertEquals('Grandchild Section', $sectionarray[3]->name);
        $this->assertEquals($sectionarray[1]->id, $sectionarray[3]->parentsection);
    }

    /**
     * Helper method to create a test file in a filearea.
     *
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     * @param string $filename
     * @param string $content
     * @return \stored_file
     */
    protected function create_test_file(
        int $contextid,
        string $component,
        string $filearea,
        int $itemid,
        string $filename = 'test.txt',
        string $content = 'Test file content'
    ): \stored_file {
        $fs = get_file_storage();

        $filerecord = [
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $filename,
        ];

        return $fs->create_file_from_string($filerecord, $content);
    }
}
