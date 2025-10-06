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

/**
 * Activity index for the mod_trainingevaluation plugin.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

// The `id` parameter is the course id.
$id = required_param('id', PARAM_INT);

// Fetch the requested course.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

// Require that the user is logged into the course.
require_course_login($course);

$PAGE->set_url('/mod/page/index.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulename', 'trainingevaluation'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
if (!$trainingevaluations = get_all_instances_in_course('trainingevaluation', $course)) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'trainingevaluation')),
        "$CFG->wwwroot/course/view.php?id=$course->id"
    );
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head = [$strsectionname, get_string('modulenameplural', 'trainingevaluation')];
    $table->align = ['center', 'left'];
} else {
    $table->head = [get_string('modulenameplural', 'trainingevaluation')];
    $table->align = ['left'];
}

foreach ($trainingevaluations as $trainingevaluation) {
    if (empty($trainingevaluation->visible)) {
        $link =
            html_writer::link(
                new moodle_url('/mod/trainingevaluation/view.php', ['id' => $trainingevaluation->coursemodule]),
                $trainingevaluation->name,
                ['class' => 'dimmed']
            );
    } else {
        $link =
            html_writer::link(
                new moodle_url('/mod/trainingevaluation/view.php', ['id' => $trainingevaluation->coursemodule]),
                $trainingevaluation->name
            );
    }

    if ($usesections) {
        $table->data[] = [get_section_name($course, $trainingevaluation->section), $link];
    } else {
        $table->data[] = [$link];
    }
}
echo $OUTPUT->heading(get_string('modulenameplural', 'trainingevaluation'), 3);
echo html_writer::table($table);

echo $OUTPUT->footer();
