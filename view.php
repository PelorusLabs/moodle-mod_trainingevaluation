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
 * Activity view page for the mod_trainingevaluation plugin.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_trainingevaluation\local\evaluation;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'trainingevaluation');
$instance = $DB->get_record('trainingevaluation', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/trainingevaluation:view', $context);

$trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $instance->id], '*', MUST_EXIST);

// Get the active evaluation for this user.
$evaluation = evaluation::get_active_evaluation($trainingevaluation->id, $USER->id);
if ($evaluation === false) {
    // No active evaluation exists - create a new one (version 1).
    $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $USER->id);
}

$PAGE->set_url('/mod/trainingevaluation/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($trainingevaluation->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_trainingevaluation');
echo $renderer->user_view($trainingevaluation, $evaluation, $context);

echo $OUTPUT->footer();
