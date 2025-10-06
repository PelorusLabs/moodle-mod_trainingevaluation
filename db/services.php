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
 * Web service definitions for mod_trainingevaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_trainingevaluation_add_section' => [
        'classname' => 'mod_trainingevaluation\external\add_section',
        'methodname' => 'execute',
        'description' => 'Add a new section to a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_update_section' => [
        'classname' => 'mod_trainingevaluation\external\update_section',
        'methodname' => 'execute',
        'description' => 'Update a section in a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_get_section' => [
        'classname' => 'mod_trainingevaluation\external\get_section',
        'methodname' => 'execute',
        'description' => 'Get a section in a training evaluation instance',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_trainingevaluation_delete_section' => [
        'classname' => 'mod_trainingevaluation\external\delete_section',
        'methodname' => 'execute',
        'description' => 'Delete a section in a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_add_item' => [
        'classname' => 'mod_trainingevaluation\external\add_item',
        'methodname' => 'execute',
        'description' => 'Add a new item to a section in a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_update_item' => [
        'classname' => 'mod_trainingevaluation\external\update_item',
        'methodname' => 'execute',
        'description' => 'Update an item in a section in a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_get_item' => [
        'classname' => 'mod_trainingevaluation\external\get_item',
        'methodname' => 'execute',
        'description' => 'Get an item in a section in a training evaluation instance',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_trainingevaluation_delete_item' => [
        'classname' => 'mod_trainingevaluation\external\delete_item',
        'methodname' => 'execute',
        'description' => 'Delete an item in a section in a training evaluation instance',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_save_response' => [
        'classname' => 'mod_trainingevaluation\external\save_response',
        'methodname' => 'execute',
        'description' => 'Save a user response to a section item.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_finalise_evaluation' => [
        'classname' => 'mod_trainingevaluation\external\finalise_evaluation',
        'methodname' => 'execute',
        'description' => 'Finalise an evaluation.',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_trainingevaluation_new_evaluation' => [
        'classname' => 'mod_trainingevaluation\external\new_evaluation',
        'methodname' => 'execute',
        'description' => 'Create a new evaluation.',
        'type' => 'write',
        'ajax' => true,
    ],
];
