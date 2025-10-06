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
 * Define the complete trainingevaluation structure for backup, with file and id annotations
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_trainingevaluation_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the trainingevaluation backup structure
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $trainingevaluation = new backup_nested_element('trainingevaluation', ['id'], [
            'name', 'intro', 'introformat', 'showlastmodified', 'completiononrequired',
        ]);

        $sections = new backup_nested_element('sections');
        $section = new backup_nested_element('section', ['id'], [
            'name', 'parentsection', 'position', 'usermodified',
            'timecreated', 'timemodified',
        ]);

        $items = new backup_nested_element('section_items');
        $item = new backup_nested_element('section_item', ['id'], [
            'name', 'description', 'type', 'position', 'isrequired',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $configs = new backup_nested_element('item_configs');
        $config = new backup_nested_element('config', ['id'], [
            'name', 'value',
        ]);

        $responses = new backup_nested_element('responses');
        $response = new backup_nested_element('response', ['id'], [
            'userid', 'response', 'completed', 'version',
            'usermodified', 'timecreated', 'timemodified',
        ]);

        $evaluations = new backup_nested_element('evaluations');
        $evaluation = new backup_nested_element('evaluation', ['id'], [
            'userid', 'finalised', 'finalisedby', 'timefinalised', 'usermodified',
            'timecreated', 'timemodified', 'version', 'active',
        ]);

        // Build the tree.
        $trainingevaluation->add_child($sections);
        $sections->add_child($section);

        $section->add_child($items);
        $items->add_child($item);

        $item->add_child($configs);
        $configs->add_child($config);

        $item->add_child($responses);
        $responses->add_child($response);

        $trainingevaluation->add_child($evaluations);
        $evaluations->add_child($evaluation);

        // Define sources.
        $trainingevaluation->set_source_table('trainingevaluation', ['id' => backup::VAR_ACTIVITYID]);

        $section->set_source_table('trainingevaluation_sections', ['wtid' => backup::VAR_PARENTID], 'position ASC');

        $item->set_source_table('trainingevaluation_section_items', ['sectionid' => backup::VAR_PARENTID], 'position ASC');

        $config->set_source_table('trainingevaluation_item_config', ['itemid' => backup::VAR_PARENTID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $response->set_source_table('trainingevaluation_responses', ['itemid' => backup::VAR_PARENTID]);
            $evaluation->set_source_table('trainingevaluation_evaluations', ['wtid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $section->annotate_ids('user', 'usermodified');
        $item->annotate_ids('user', 'usermodified');
        $response->annotate_ids('user', 'userid');
        $response->annotate_ids('user', 'usermodified');
        $evaluation->annotate_ids('user', 'userid');
        $evaluation->annotate_ids('user', 'finalisedby');
        $evaluation->annotate_ids('user', 'usermodified');

        // Define file annotations.
        $trainingevaluation->annotate_files('mod_trainingevaluation', 'intro', null);

        // Return the root element (trainingevaluation), wrapped into a standard activity structure.
        return $this->prepare_activity_structure($trainingevaluation);
    }
}
