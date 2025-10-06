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
 * Section actions
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$wtid = required_param('wtid', PARAM_INT);

$trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $wtid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $trainingevaluation->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

require_capability('mod/trainingevaluation:manage', context_module::instance($cm->id));

$PAGE->set_url('/mod/trainingevaluation/sections.php', ['wtid' => $wtid]);
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($cm->name));

echo $OUTPUT->header();

echo $OUTPUT->heading($PAGE->heading);

echo $OUTPUT->footer();
