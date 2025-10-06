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

namespace mod_trainingevaluation\reportbuilder\local\systemreports;

use coding_exception;
use context_module;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\system_report;
use mod_trainingevaluation\reportbuilder\local\entities\evaluation;

/**
 * Evaluations system report class implementation
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluations extends system_report {
    /**
     * Initialise report
     *
     * @return void
     */
    protected function initialise(): void {
        $cmid = $this->get_parameter('cmid', 0, PARAM_INT);
        $wtid = $this->get_parameter('wtid', 0, PARAM_INT);

        $evaluationentity = new evaluation($cmid, $wtid);
        $userenrolmentalias = $evaluationentity->get_table_alias('user_enrolments');
        $this->set_main_table('user_enrolments', $userenrolmentalias);

        $this->add_entity($evaluationentity);

        $userentity = new user();
        $userentityalias = $userentity->get_table_alias('user');

        $userentityjoin = "JOIN {user} {$userentityalias} ON {$userentityalias}.id = {$userenrolmentalias}.userid";
        $this->add_entity($userentity->add_join($userentityjoin));

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('user:fullname', SORT_ASC);
    }

    /**
     * Check if user can view report
     *
     * @return bool
     */
    protected function can_view(): bool {
        $cmid = $this->get_parameter('cmid', 0, PARAM_INT);
        $wtid = $this->get_parameter('wtid', 0, PARAM_INT);

        if (!$cmid || !$wtid) {
            return false;
        }

        $context = context_module::instance($cmid);
        return has_capability('mod/trainingevaluation:evaluate', $context);
    }

    /**
     * Add columns
     *
     * @return void
     */
    public function add_columns(): void {
        $this->add_columns_from_entities([
            'user:fullname',
            'evaluation:completionstatus',
            'evaluation:finalised',
            'evaluation:version',
            'evaluation:evaluatelink',
        ]);
    }

    /**
     * Add filters
     *
     * @return void
     */
    public function add_filters(): void {
        $this->add_filters_from_entities([
        ]);
    }
}
