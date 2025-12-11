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
 * Core callbacks for the mod_trainingevaluation plugin.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checks if trainingevaluation supports a specific feature.
 *
 * @param $feature
 * @return int|string|null
 */
function trainingevaluation_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Add instance
 *
 * @param object $data
 * @param mod_trainingevaluation_mod_form|null $mform
 * @return int
 */
function trainingevaluation_add_instance(stdClass $data, mod_trainingevaluation_mod_form|null $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('trainingevaluation', $data);

    return $data->id;
}

/**
 * Update instance data
 *
 * @param stdClass $data
 * @param mod_trainingevaluation_mod_form|null $mform
 * @return true
 */
function trainingevaluation_update_instance(stdClass $data, mod_trainingevaluation_mod_form|null $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $DB->update_record('trainingevaluation', $data);

    return true;
}

/**
 * Removes an instance of the mod_trainingevaluation from the database.
 *
 * @param int $id id of the module instance.
 * @return bool True if successful, false on failure.
 */
function trainingevaluation_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('trainingevaluation', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $sections = \mod_trainingevaluation\local\section::get_records(['wtid' => $id]);

    foreach ($sections as $section) {
        $section->delete_with_child_data();
    }

    $DB->delete_records('trainingevaluation', ['id' => $id]);

    return true;
}

/**
 * Extends the settings navigation with trainingevaluation settings.
 *
 * @param settings_navigation $settingsnav Navigation tree
 * @param navigation_node|null $trainingevaluationnode trainingevaluation node
 */
function trainingevaluation_extend_settings_navigation(
    settings_navigation $settingsnav,
    navigation_node|null $trainingevaluationnode = null
) {
    global $PAGE;

    if (has_capability('mod/trainingevaluation:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(
            get_string('editsections', 'trainingevaluation'),
            new moodle_url('/mod/trainingevaluation/manage.php', ['cmid' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_trainingevaluation_manage',
            new pix_icon('t/edit', '')
        );
        $trainingevaluationnode->add_node($node);
    }
    if (has_capability('mod/trainingevaluation:evaluate', $PAGE->cm->context)) {
        $node = navigation_node::create(
            get_string('evaluatestudents', 'trainingevaluation'),
            new moodle_url('/mod/trainingevaluation/evaluatestudents.php', ['cmid' => $PAGE->cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_trainingevaluation_evaluate',
            new pix_icon('t/edit', '')
        );
        $trainingevaluationnode->add_node($node);
    }
}

/**
 * Serves trainingevaluation files.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The cm object.
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args List of arguments.
 * @param bool $forcedownload Whether or not to force the download of the file.
 * @param array $options Array of options.
 */
function trainingevaluation_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);
    require_capability('mod/trainingevaluation:view', $context);

    $itemid = array_shift($args);
    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_trainingevaluation', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param $coursemodule
 * @return cached_cm_info|false
 * @throws dml_exception
 */
function trainingevaluation_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completiononrequired';
    if (!$trainingevaluation = $DB->get_record('trainingevaluation', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $trainingevaluation->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('trainingevaluation', $trainingevaluation, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completiononrequired'] = $trainingevaluation->completiononrequired;
    }

    return $result;
}
