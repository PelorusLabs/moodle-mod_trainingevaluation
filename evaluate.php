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
 * Evaluation page
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\exception\moodle_exception;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\section_item;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);
$itemid = optional_param('itemid', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$version = optional_param('version', null, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'trainingevaluation');

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

if (!evaluation::can_evaluate_user($context, $userid)) {
    throw new moodle_exception(
        'nopermissiontoevaluate',
        'mod_trainingevaluation',
        new moodle_url('/mod/trainingevaluation/view.php', ['id' => $cm->id]),
        null,
        'User attempted to evaluate without permission'
    );
}

$trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $cm->instance], '*', MUST_EXIST);

if ($version === null) {
    // Get the active evaluation for this user.
    $evaluation = evaluation::get_active_evaluation($trainingevaluation->id, $userid);
    if ($evaluation === false) {
        // No active evaluation exists - create a new one (version 1).
        $evaluation = evaluation::get_record_create_if_not_exists($trainingevaluation->id, $userid);
    }
} else {
    $evaluation = evaluation::get_record(['wtid' => $trainingevaluation->id, 'userid' => $userid, 'version' => $version]);
    if ($evaluation === false) {
        throw new moodle_exception(
            'evaluationversionnotfound',
            'mod_trainingevaluation',
            new moodle_url('/mod/trainingevaluation/evaluate.php', ['cmid' => $cm->id, 'userid' => $userid])
        );
    }
}


$PAGE->set_url('/mod/trainingevaluation/evaluate.php', ['cmid' => $cm->id]);
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($cm->name) . ' - ' . get_string('evaluatestudents', 'trainingevaluation'));

if ($action == 'fileupload' && $itemid != null && $itemid != 0) {
    $sectionitem = new section_item($itemid);
    $sectionitem->get_type_instance()->save_response($cm, $trainingevaluation, $sectionitem, $evaluation, null);
}

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_trainingevaluation');
echo $renderer->evaluate_view($trainingevaluation, $evaluation, $context);

echo $OUTPUT->footer();
