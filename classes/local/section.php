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

namespace mod_trainingevaluation\local;

/**
 * Class for section data
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core\persistent {
    /**
     * Database data.
     */
    public const TABLE = 'trainingevaluation_sections';

    /**
     * Defines and returns the properties of the class.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'wtid' => [
                'type' => PARAM_INT,
                'description' => 'The training evaluation instance.',
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'The section name.',
            ],
            'parentsection' => [
                'type' => PARAM_INT,
                'description' => 'The parent section ID, if one exists.',
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'position' => [
                'type' => PARAM_INT,
                'description' => 'The position in the list',
            ],
        ];
    }

    /**
     * Section items
     *
     * @return array
     */
    public function get_items(): array {
        return section_item::get_records(['sectionid' => $this->get('id')], 'position');
    }

    /**
     * Subsections
     *
     * @return array
     */
    public function get_subsections(): array {
        return self::get_records(['parentsection' => $this->get('id')], 'position');
    }

    /**
     * Deletes the current section along with its associated subsections and items.
     *
     * This includes deleting all items and recursively deleting data within any subsections.
     *
     * @return bool Returns true if the deletion process is successful; otherwise, false.
     */
    public function delete_with_child_data(): bool {
        foreach ($this->get_items() as $item) {
            $item->delete_response_data();
            $item->delete_config_data();
            $item->delete();
        }
        foreach ($this->get_subsections() as $subsection) {
            $subsection->delete_with_child_data();
        }
        return $this->delete();
    }

    /**
     * Reorders the positions of the subsections.
     *
     * @return void
     */
    public function reorder_subsection_positions() {
        $position = 0;
        foreach ($this->get_subsections() as $subsection) {
            $subsection->set('position', $position);
            $subsection->update();
            $position++;
        }
    }

    /**
     * Reorders the positions of section items.
     *
     * @return void
     */
    public function reorder_items() {
        $position = 0;
        foreach ($this->get_items() as $item) {
            $item->set('position', $position);
            $item->update();
            $position++;
        }
    }

    /**
     * Get the max position this section could possibly be.
     *
     * @return int
     */
    public function get_max_position(): int {
        global $DB;

        if ($this->get('parentsection')) {
            return $DB->get_field_sql(
                'SELECT MAX(position) FROM {trainingevaluation_sections}
             WHERE parentsection = ?',
                [$this->get('parentsection')]
            ) ?? 0;
        } else {
            // Top level position.
            return $DB->get_field_sql(
                'SELECT MAX(position) FROM {trainingevaluation_sections}
             WHERE parentsection IS NULL AND wtid = ?',
                [$this->get('wtid')]
            ) ?? 0;
        }
    }

    /**
     * Move section up within surrounding sections.
     *
     * @return void
     */
    public function move_up(): void {
        $currentposition = $this->get('position');
        if ($currentposition == 0) {
            // Already at the top, can't go higher.
            return;
        }
        $newposition = $currentposition - 1;
        $parentsection = $this->get('parentsection') ?? null;
        $belowsection =
            self::get_record(['wtid' => $this->get('wtid'), 'position' => $newposition, 'parentsection' => $parentsection]);
        $this->set('position', $newposition);
        $this->update();
        $belowsection->set('position', $belowsection->get('position') + 1);
        $belowsection->update();
    }

    /**
     * Move section down within surrounding sections.
     *
     * @return void
     */
    public function move_down(): void {
        $currentposition = $this->get('position');
        if ($currentposition == $this->get_max_position()) {
            // Already at the bottom, can't go lower.
            return;
        }
        $parentsection = $this->get('parentsection') ?? null;
        $newposition = $currentposition + 1;
        $abovesection =
            self::get_record(['wtid' => $this->get('wtid'), 'position' => $newposition, 'parentsection' => $parentsection]);
        $this->set('position', $newposition);
        $this->update();
        $abovesection->set('position', $abovesection->get('position') - 1);
        $abovesection->update();
    }
}
