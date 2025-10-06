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

namespace mod_trainingevaluation\local\types;

use context_module;
use core\context;
use html_writer;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section_item;
use moodle_url;
use stdClass;

/**
 * File upload item type
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_fileupload extends base {
    /**
     * Render the manage sections form.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param int $userid
     * @return string
     */
    public function render_manage_form(stdClass $trainingevaluation, section_item $item, int $userid): string {
        return \html_writer::tag(
            'p',
            get_string('fileuploadplaceholder', 'trainingevaluation'),
            ['class' => 'mod-trainingevaluation-fileupload-placeholder']
        );
    }

    /**
     * Render evaluation form.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    public function render_evaluate_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string {
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $config = $this->get_config($item);

        $out = '';

        $dataitem = new \stdClass();
        $dataitem->itemid = $item->get('id');
        $dataitem->userid = $evaluation->get('userid');
        $dataitem->filetypes = $config['filetypes'] ?? '*';

        $out .= $this->render_file_list($context, $item, $evaluation);

        $dataitem = file_prepare_standard_filemanager(
            $dataitem,
            'type_fileupload',
            ['subdirs' => 0, 'maxfiles' => -1, 'accepted_types' => $dataitem->filetypes],
            $context,
            'mod_trainingevaluation',
            "type_fileupload_{$dataitem->itemid}_{$evaluation->get('version')}",
            $evaluation->get('userid')
        );

        $form = new \mod_trainingevaluation\form\fileupload_form(new \moodle_url('/mod/trainingevaluation/evaluate.php', [
            'cmid' => $cm->id,
            'itemid' => $item->get('id'),
            'userid' => $evaluation->get('userid'),
            'action' => 'fileupload',
        ]), ['itemid' => $item->get('id'), 'userid' => $evaluation->get('userid'), 'filetypes' => $dataitem->filetypes]);
        $form->set_data($dataitem);
        $out .= $form->render();

        return $out;
    }

    /**
     * Render the user view form.
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    public function render_user_form(stdClass $trainingevaluation, section_item $item, evaluation $evaluation): string {
        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        return $this->render_file_list($context, $item, $evaluation);
    }

    /**
     * Render file list.
     *
     * @param context $context
     * @param section_item $item
     * @param evaluation $evaluation
     * @return string
     */
    private function render_file_list(context $context, $item, $evaluation): string {
        $out = '';

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'mod_trainingevaluation',
            "type_fileupload_{$item->get('id')}_{$evaluation->get('version')}",
            $evaluation->get('userid'),
            'itemid, filepath, filename',
            false
        );

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                true
            );
            $out .= html_writer::link($url, $filename);
            $out .= html_writer::empty_tag('br');
        }

        return $out;
    }

    /**
     * Save the response.
     *
     * @param $cm
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param evaluation $evaluation
     * @param $responsedata
     * @return bool
     */
    public function save_response($cm, stdClass $trainingevaluation, section_item $item, evaluation $evaluation, $responsedata): bool {
        global $CFG;

        require_once("{$CFG->libdir}/formslib.php");

        $cm = get_coursemodule_from_instance('trainingevaluation', $trainingevaluation->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $dataitem = new \stdClass();
        $dataitem->itemid = $item->get('id');
        $dataitem->userid = $evaluation->get('userid');
        $dataitem->filetypes = $config['filetypes'] ?? '*';
        $dataitem = file_prepare_standard_filemanager(
            $dataitem,
            'type_fileupload',
            ['subdirs' => 0, 'maxfiles' => -1, 'accepted_types' => $dataitem->filetypes],
            $context,
            'mod_trainingevaluation',
            "type_fileupload_{$dataitem->itemid}_{$evaluation->get('version')}",
            $evaluation->get('userid')
        );

        $form = new \mod_trainingevaluation\form\fileupload_form(new \moodle_url('/mod/trainingevaluation/evaluate.php', [
            'cmid' => $cm->id,
            'userid' => $evaluation->get('userid'),
            'itemid' => $item->get('id'),
            'action' => 'fileupload',
        ]), ['itemid' => $item->get('id'), 'userid' => $evaluation->get('userid'), 'filetypes' => $dataitem->filetypes]);
        $form->set_data($dataitem);
        if ($data = $form->get_data()) {
            file_postupdate_standard_filemanager(
                $dataitem,
                'type_fileupload',
                [],
                $context,
                'mod_trainingevaluation',
                "type_fileupload_{$dataitem->itemid}_{$evaluation->get('version')}",
                $evaluation->get('userid')
            );

            parent::save_response($cm, $trainingevaluation, $item, $evaluation, '');

            redirect(new \moodle_url('/mod/trainingevaluation/evaluate.php', [
                'cmid' => $cm->id,
                'userid' => $evaluation->get('userid'),
            ]));
        }
        return true;
    }

    /**
     * Get the config structure for this type.
     *
     * @return array
     */
    public function get_config_structure(): array {
        return [
            'filetypes' => ['type' => PARAM_TEXT],
        ];
    }

    /**
     * Check if files exist, determines if user has completed the item.
     *
     * @param int $itemid
     * @param int $userid
     * @param int $version
     * @return bool
     */
    public function has_user_completed(int $itemid, int $userid, int $version): bool {
        return true;
    }
}
