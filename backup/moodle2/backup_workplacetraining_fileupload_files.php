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
 * Custom backup step to handle dynamic fileupload file areas
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_trainingevaluation_fileupload_files extends backup_execution_step {
    /**
     * Define the execution
     *
     * @return void
     */
    protected function define_execution() {
        global $DB;

        $userinfo = $this->get_setting_value('userinfo');

        if (!$userinfo) {
            return;
        }

        $activityid = $this->task->get_activityid();
        $wt = $DB->get_record('trainingevaluation', ['id' => $activityid]);
        $cm = get_coursemodule_from_instance('trainingevaluation', $wt->id);
        $context = context_module::instance($cm->id);

        // Get all fileupload items in this activity.
        $sql = "SELECT wsi.id, wsi.type
                  FROM {trainingevaluation_section_items} wsi
                  JOIN {trainingevaluation_sections} ws ON ws.id = wsi.sectionid
                 WHERE ws.wtid = ? AND wsi.type = ?";
        $wtitems = $DB->get_records_sql($sql, [$activityid, 'fileupload']);

        // For each fileupload item, find responses and their version, then backup files by user.
        foreach ($wtitems as $wtitem) {
            $sql = "SELECT r.id, r.itemid, r.userid, r.version FROM {trainingevaluation_responses} r WHERE r.itemid = :itemid";
            $responses = $DB->get_records_sql($sql, ['itemid' => $wtitem->id]);
            foreach ($responses as $response) {
                $filearea = 'type_fileupload_' . $wtitem->id . '_' . $response->version;

                // Get all users who have uploaded files for this item.
                $fs = get_file_storage();
                $files = $fs->get_area_files($context->id, 'mod_trainingevaluation', $filearea, $response->userid, 'itemid', false);

                if (!empty($files)) {
                    // Annotate files for each user.
                    backup_structure_dbops::annotate_files(
                        $this->task->get_backupid(),
                        $context->id,
                        'mod_trainingevaluation',
                        $filearea,
                        $response->userid
                    );
                }
            }
        }
    }
}
