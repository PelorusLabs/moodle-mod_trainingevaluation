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
 * Class for response data
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response extends \core\persistent {
    /**
     * Database data.
     */
    public const TABLE = 'trainingevaluation_responses';

    /**
     * Defines and returns the properties of the class.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'itemid' => [
                'type' => PARAM_INT,
                'description' => 'The item ID.',
            ],
            'userid' => [
                'type' => PARAM_INT,
                'description' => 'The user ID.',
            ],
            'response' => [
                'type' => PARAM_TEXT,
                'description' => 'Response data.',
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'completed' => [
                'type' => PARAM_BOOL,
                'description' => 'Whether the item is completed',
                'default' => false,
            ],
            'version' => [
                'type' => PARAM_INT,
                'description' => 'The version of the response.',
                'default' => 1,
            ],
        ];
    }
}
