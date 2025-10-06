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

use mod_trainingevaluation\local\types\base;

/**
 * Class for section item data
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_item extends \core\persistent {
    /**
     * Database data.
     */
    public const TABLE = 'trainingevaluation_section_items';

    /**
     * Defines and returns the properties of the class.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'sectionid' => [
                'type' => PARAM_INT,
                'description' => 'The section ID.',
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'The item name.',
            ],
            'description' => [
                'type' => PARAM_TEXT,
                'description' => 'The item description.',
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'isrequired' => [
                'type' => PARAM_BOOL,
                'description' => 'Whether the item is required for completion',
                'default' => true,
            ],
            'type' => [
                'type' => PARAM_TEXT,
                'description' => 'The type of item.',
            ],
            'position' => [
                'type' => PARAM_INT,
                'description' => 'The position in the list',
            ],
        ];
    }

    /**
     * Get item type name
     *
     * @return string
     */
    public function get_type_name(): string {
        return get_string('itemtype' . $this->get('type'), 'mod_trainingevaluation');
    }

    /**
     * Get item type instance
     *
     * @return base|null
     */
    public function get_type_instance(): ?base {
        $classname = "\\mod_trainingevaluation\\local\\types\\type_{$this->get('type')}";
        if (class_exists($classname)) {
            return new $classname();
        }
        return null;
    }

    /**
     * Get item's section
     *
     * @return section
     */
    public function get_section(): section {
        return new section($this->get('sectionid'));
    }

    /**
     * Get the max position this item could possibly be.
     *
     * @return int
     */
    public function get_max_position(): int {
        global $DB;

        return $DB->get_field_sql(
            'SELECT MAX(position) FROM {trainingevaluation_section_items}
             WHERE sectionid = ?',
            [$this->get('sectionid')]
        ) ?? 0;
    }

    /**
     * Move item up within surrounding items.
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
        $belowitem = self::get_record(['sectionid' => $this->get('sectionid'), 'position' => $newposition]);
        $this->set('position', $newposition);
        $this->update();
        $belowitem->set('position', $belowitem->get('position') + 1);
        $belowitem->update();
    }

    /**
     * Move item down within surrounding items.
     *
     * @return void
     */
    public function move_down(): void {
        $currentposition = $this->get('position');
        if ($currentposition == $this->get_max_position()) {
            // Already at the bottom, can't go lower.
            return;
        }
        $newposition = $currentposition + 1;
        $aboveitem = self::get_record(['sectionid' => $this->get('sectionid'), 'position' => $newposition]);
        $this->set('position', $newposition);
        $this->update();
        $aboveitem->set('position', $aboveitem->get('position') - 1);
        $aboveitem->update();
    }

    /**
     * Delete all responses for this item.
     *
     * @return bool
     */
    public function delete_response_data(): bool {
        global $DB;
        return $DB->delete_records('trainingevaluation_responses', ['itemid' => $this->get('id')]);
    }

    /**
     * Delete all config data for this item.
     *
     * @return bool
     */
    public function delete_config_data(): bool {
        global $DB;
        return $DB->delete_records('trainingevaluation_item_config', ['itemid' => $this->get('id')]);
    }
}
