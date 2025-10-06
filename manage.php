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
 * Manage sections page
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$cmid = required_param('cmid', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'trainingevaluation');

require_login($course, true, $cm);
require_capability('mod/trainingevaluation:manage', context_module::instance($cm->id));

$trainingevaluation = $DB->get_record('trainingevaluation', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/trainingevaluation/manage.php', ['cmid' => $cm->id]);
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($cm->name));

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_trainingevaluation');
echo $renderer->manage_view($trainingevaluation);

echo $OUTPUT->footer();
