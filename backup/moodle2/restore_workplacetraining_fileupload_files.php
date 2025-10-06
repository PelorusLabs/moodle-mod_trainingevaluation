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
 * Custom restore step to handle dynamic fileupload file areas
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_trainingevaluation_fileupload_files extends restore_execution_step {
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

        $wtid = $this->task->get_activityid();

        $sql = "SELECT wsi.id AS newid, wsi.type
                  FROM {trainingevaluation_section_items} wsi
                  JOIN {trainingevaluation_sections} ws ON ws.id = wsi.sectionid
                 WHERE ws.wtid = ? AND wsi.type = ?";
        $newitems = $DB->get_records_sql($sql, [$wtid, 'fileupload']);

        $oldcontextid = $this->task->get_old_contextid();
        $newcontextid = $this->task->get_contextid();

        // For each fileupload item, restore files to its dynamic filearea.
        foreach ($newitems as $newitem) {
            $olditemid = $this->get_mappingid('trainingevaluation_section_item', $newitem->newid);

            $sql = "SELECT r.id, r.itemid, r.userid, r.version FROM {trainingevaluation_responses} r WHERE r.itemid = :itemid";
            $responses = $DB->get_records_sql($sql, ['itemid' => $newitem->newid]);

            foreach ($responses as $response) {
                if ($olditemid) {
                    $oldfilearea = 'type_fileupload_' . $olditemid . '_' . $response->version;
                    $newfilearea = 'type_fileupload_' . $newitem->newid . '_' . $response->version;

                    // Restore files from the backup.
                    $results = restore_dbops::send_files_to_pool(
                        $this->get_basepath(),
                        $this->get_restoreid(),
                        'mod_trainingevaluation',
                        $oldfilearea,
                        $oldcontextid,
                        $this->task->get_userid(),
                    );

                    // If the item IDs are different, we need to rename the filearea.
                    if ($olditemid != $newitem->newid) {
                        $fs = get_file_storage();
                        $files = $fs->get_area_files($newcontextid, 'mod_trainingevaluation', $oldfilearea, false, '', false);

                        foreach ($files as $file) {
                            $filerecord = [
                                'filearea' => $newfilearea,
                            ];

                            $fs->create_file_from_storedfile($filerecord, $file);

                            $file->delete();
                        }
                    }

                    foreach ($results as $result) {
                        $this->log($result->message, $result->level);
                    }
                }
            }
        }
    }

    /**
     * Helper method to get mapping id (returns the old id, not new).
     *
     * @param string $itemname
     * @param int $newid
     * @param bool $returnnull
     * @return int|null
     */
    protected function get_mappingid($itemname, $newid, $returnnull = false) {
        global $DB;

        $mapping = $DB->get_record('backup_ids_temp', [
            'backupid' => $this->get_restoreid(),
            'itemname' => $itemname,
            'newitemid' => $newid,
        ]);

        if ($mapping) {
            return $mapping->itemid;
        }

        return $returnnull ? null : 0;
    }
}
