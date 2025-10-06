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

namespace mod_trainingevaluation\output;

use core_reportbuilder\system_report_factory;
use core_table\output\html_table;
use core_table\output\html_table_row;
use html_writer;
use mod_trainingevaluation\local\evaluation;
use mod_trainingevaluation\local\response;
use mod_trainingevaluation\local\section;
use mod_trainingevaluation\local\section_item;
use mod_trainingevaluation\reportbuilder\local\systemreports\evaluations;
use moodle_url;
use plugin_renderer_base;
use stdClass;

/**
 * Renderer class for training evaluation.
 *
 * @package    mod_trainingevaluation
 * @copyright  Pelorus Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Read only view.
     */
    public const VIEW_TYPE_READONLY = 'readonly';
    /**
     * Evaluation view.
     */
    public const VIEW_TYPE_EVALUATE = 'evaluate';
    /**
     * Manage view.
     */
    public const VIEW_TYPE_MANAGE = 'manage';

    /**
     * Render evaluation page listing students.
     *
     * @param stdClass $trainingevaluation
     * @param $cm
     * @param $context
     * @return string
     */
    public function evaluate_students(stdClass $trainingevaluation, $cm, $context): string {
        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-evaluate-students-container']);

        $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-evaluate-students-header']);
        $out .= html_writer::tag('h2', get_string('evaluatestudents', 'trainingevaluation'), ['class' => 'mb-4']);
        $out .= html_writer::end_tag('div');

        $report = system_report_factory::create(
            evaluations::class,
            $context,
            '',
            '',
            0,
            [
                'cmid' => $cm->id,
                'wtid' => $trainingevaluation->id,
            ]
        );

        if (!empty($filter)) {
            $report->set_filter_values($filter);
        }

        $out .= $report->output();

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Render read-only user view.
     *
     * @param stdClass $trainingevaluation
     * @param evaluation $evaluation
     * @param $context
     * @return string
     */
    public function user_view(stdClass $trainingevaluation, $evaluation, $context): string {
        global $DB;

        $this->page->requires->js_call_amd('mod_trainingevaluation/user_sections', 'init');

        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-evaluation-summary d-flex mb-3']);

        $user = $DB->get_record('user', ['id' => $evaluation->get('userid')], '*', MUST_EXIST);
        $out .= html_writer::tag('h3', fullname($user), ['class' => 'mr-3 mt-auto']);

        $out .= html_writer::end_tag('div');

        $totalrequired = $evaluation->get_total_items_required();
        $completedcount = $evaluation->get_total_items_completed();
        $out .= self::format_completion_status($completedcount, $totalrequired);

        $out .= $this->render_sections($trainingevaluation, self::VIEW_TYPE_READONLY, $evaluation->get('userid'), $evaluation);

        return $out;
    }

    /**
     * Render evaluation view.
     *
     * @param stdClass $trainingevaluation
     * @param evaluation $evaluation
     * @param $context
     * @return string
     */
    public function evaluate_view(stdClass $trainingevaluation, evaluation $evaluation, $context): string {
        global $DB;

        $this->page->requires->js_call_amd(
            'mod_trainingevaluation/evaluate_sections',
            'init',
            [$trainingevaluation->id, $evaluation->get('userid')]
        );

        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-evaluation-summary d-flex mb-3']);

        $user = $DB->get_record('user', ['id' => $evaluation->get('userid')], '*', MUST_EXIST);
        $out .= html_writer::tag('h3', fullname($user), ['class' => 'mr-3 mt-auto']);

        if ($evaluation->get('finalised')) {
            $finalisedby = $DB->get_record('user', ['id' => $evaluation->get('finalisedby')], '*', MUST_EXIST);
            $out .= html_writer::div(
                html_writer::tag('i', '', ['class' => 'fa fa-lock mr-2']) .
                get_string('evaluationfinalised', 'trainingevaluation', [
                    'name' => fullname($finalisedby),
                    'date' => userdate($evaluation->get('timefinalised')),
                ]),
                'alert alert-info mb-0 mr-3'
            );

            if (has_capability('mod/trainingevaluation:newevaluation', $context) && $evaluation->get('active')) {
                $out .= html_writer::tag(
                    'button',
                    get_string('createnewevaluation', 'trainingevaluation'),
                    [
                        'class' => 'btn btn-primary',
                        'id' => 'mod-trainingevaluation-new-evaluation-btn',
                        'data-userid' => $evaluation->get('userid'),
                    ]
                );
            }
        } else {
            if (has_capability('mod/trainingevaluation:finaliseevaluation', $context)) {
                $out .= html_writer::tag(
                    'button',
                    get_string('finaliseevaluation', 'trainingevaluation'),
                    [
                        'class' => 'btn btn-primary',
                        'id' => 'mod-trainingevaluation-finalise-btn',
                        'data-userid' => $evaluation->get('userid'),
                    ]
                );
            }
        }

        if (has_capability('mod/trainingevaluation:viewoldevaluations', $context)) {
            if (evaluation::get_number_of_versions($trainingevaluation->id, $evaluation->get('userid')) > 1) {
                $out .= $this->render_version_selector($trainingevaluation->id, $evaluation);
            }
        }

        $out .= html_writer::end_tag('div');

        $totalrequired = $evaluation->get_total_items_required();
        $completedcount = $evaluation->get_total_items_completed();
        $out .= self::format_completion_status($completedcount, $totalrequired);

        $out .= $this->render_sections(
            $trainingevaluation,
            $evaluation->get('finalised') ? self::VIEW_TYPE_READONLY : self::VIEW_TYPE_EVALUATE,
            $evaluation->get('userid'),
            $evaluation
        );

        return $out;
    }

    /**
     * Render manage view
     *
     * @param stdClass $trainingevaluation
     * @return string
     */
    public function manage_view(stdClass $trainingevaluation): string {
        global $USER;

        $this->page->requires->js_call_amd('mod_trainingevaluation/manage_sections', 'init', [$trainingevaluation->id]);

        $out = html_writer::tag(
            'div',
            html_writer::tag(
                'button',
                get_string('addsection', 'trainingevaluation'),
                [
                    'class' => 'btn btn-primary',
                    'id' => 'mod-trainingevaluation-add-section-btn',
                ]
            )
        );

        $out .= $this->render_sections($trainingevaluation, self::VIEW_TYPE_MANAGE, $USER->id, null);

        return $out;
    }

    /**
     * Render the sections
     *
     * @param stdClass $trainingevaluation
     * @param string $viewtype
     * @param int $userid
     * @param evaluation|null $evaluation
     * @return string
     * @throws \coding_exception
     */
    private function render_sections(stdClass $trainingevaluation, string $viewtype, int $userid, evaluation|null $evaluation): string {
        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-sections mt-3']);

        // Get only top level sections.
        $sections = section::get_records(['wtid' => $trainingevaluation->id, 'parentsection' => null], 'position');
        if (!empty($sections)) {
            foreach ($sections as $section) {
                $out .= $this->render_section($trainingevaluation, $section, 0, $viewtype, $userid, $evaluation);
            }
        } else {
            $out .= html_writer::tag(
                'div',
                get_string('nosections', 'trainingevaluation'),
                ['class' => 'alert alert-info']
            );
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Render sections for management
     *
     * @param stdClass $trainingevaluation
     * @param section $section
     * @param int $level
     * @param string $viewtype
     * @param int $userid
     * @param evaluation|null $evaluation
     * @return string
     */
    private function render_section(
        stdClass $trainingevaluation,
        section $section,
        int $level,
        string $viewtype,
        int $userid,
        evaluation|null $evaluation
    ): string {
        $sectionid = 'section-' . $section->get('id');
        // Collapse child sections by default.
        $collapsed = false;

        // Section container.
        $out = html_writer::start_tag(
            'div',
            ['class' => 'mod-trainingevaluation-section mod-trainingevaluation-section-level-' . $level,
                'data-section-id' => $section->get('id')]
        );

        // Section header.
        $out .= html_writer::start_tag(
            'div',
            ['class' => 'mod-trainingevaluation-section-header d-flex justify-content-between align-items-start']
        );

        // Section title with collapse toggle.
        $toggleattrs = [
            'class' => 'd-flex align-items-center mod-trainingevaluation-section-toggle' . ($collapsed ? ' collapsed' : ''),
            'data-toggle' => 'collapse',
            'data-target' => '#' . $sectionid . '-content',
            'aria-expanded' => $collapsed ? 'false' : 'true',
            'aria-controls' => $sectionid . '-content',
        ];
        $out .= html_writer::start_tag('div', $toggleattrs);
        $out .= html_writer::tag(
            'span',
            '<i class="fa fa-caret-down mr-2"></i>',
            ['class' => 'mod-trainingevaluation-section-toggle-icon']
        );
        $out .= html_writer::tag(
            'h3',
            format_string($section->get('name')),
            ['class' => 'mod-trainingevaluation-section-title m-0 ml-2']
        );
        $out .= html_writer::end_tag('div');

        if ($viewtype == self::VIEW_TYPE_MANAGE) {
            // Section actions.
            $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-section-actions']);
            // Add item button.
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-plus"></i> ' . get_string('additem', 'trainingevaluation'),
                [
                    'class' => 'btn btn-sm btn-outline-secondary mr-2 mod-trainingevaluation-add-item-btn',
                    'data-section-id' => $section->get('id'),
                ]
            );
            // Add a subsection button.
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-folder-plus"></i> ' . get_string('addsection', 'trainingevaluation'),
                [
                    'class' => 'btn btn-sm btn-outline-secondary mr-2 mod-trainingevaluation-add-section-btn',
                    'data-parent-section-id' => $section->get('id'),
                ]
            );
            $out .= html_writer::tag('span', '&nbsp;', ['class' => 'mod-trainingevaluation-section-actions-spacer']);

            $out .= $this->reorder_buttons($section->get('id'), $section->get('position'), $section->get_max_position(), 'section');

            // Edit button.
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-edit"></i>',
                ['class' => 'btn btn-sm btn-outline-secondary mr-2 mod-trainingevaluation-edit-section-btn',
                    'data-section-id' => $section->get('id')]
            );
            // Delete button.
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-trash"></i>',
                ['class' => 'btn btn-sm btn-outline-secondary mod-trainingevaluation-delete-section-btn',
                    'data-section-id' => $section->get('id')]
            );

            $out .= html_writer::end_tag('div'); // End section-actions.
        }

        $out .= html_writer::end_tag('div'); // End section header.

        // Section content (collapsible).
        $out .= html_writer::start_tag('div', [
            'id' => $sectionid . '-content',
            'class' => 'section-content collapse' . ($collapsed ? '' : ' show'),
        ]);

        $items = $section->get_items();

        $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-section-items mx-4 mt-3']);
        if (!empty($items)) {
            foreach ($items as $item) {
                $out .= $this->render_item($trainingevaluation, $item, $viewtype, $userid, $evaluation);
            }
        } else {
            if ($viewtype == self::VIEW_TYPE_MANAGE) {
                $out .= html_writer::tag('p', get_string('noitems', 'trainingevaluation'), ['class' => 'alert alert-info']);
            }
        }
        $out .= html_writer::end_tag('div');

        $subsections = $section->get_subsections();
        if (!empty($subsections)) {
            $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-section-children mx-4 mt-3']);
            foreach ($subsections as $subsection) {
                $out .= $this->render_section($trainingevaluation, $subsection, $level + 1, $viewtype, $userid, $evaluation);
            }
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div'); // End section content.
        $out .= html_writer::end_tag('div'); // End section.

        return $out;
    }

    /**
     * Render section item management
     *
     * @param stdClass $trainingevaluation
     * @param section_item $item
     * @param string $viewtype
     * @param int $userid
     * @param evaluation|null $evaluation
     * @return string
     */
    private function render_item(
        stdClass $trainingevaluation,
        section_item $item,
        string $viewtype,
        int $userid,
        evaluation|null $evaluation
    ): string {
        global $DB;

        $out = html_writer::start_tag(
            'div',
            ['class' => 'mod-trainingevaluation-section-item mb-4', 'data-item-id' => $item->get('id')]
        );

        // Item header.
        $out .= html_writer::start_tag(
            'div',
            ['class' => 'mod-trainingevaluation-item-header d-flex justify-content-between align-items-start']
        );

        // Item title.
        $out .= html_writer::tag('h4', format_string($item->get('name')), ['class' => 'mod-trainingevaluation-item-title']);

        $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-item-details']);
        if ($item->get('isrequired')) {
            $out .= html_writer::span('Required', 'badge badge-danger');
        } else {
            $out .= html_writer::span('Optional', 'badge badge-info');
        }
        $out .= html_writer::end_tag('div');

        if ($viewtype == self::VIEW_TYPE_MANAGE) {
            $out .= html_writer::tag('span', '&nbsp;&nbsp;', ['class' => 'mod-trainingevaluation-section-actions-spacer']);

            // Item actions (edit, delete, etc.).
            $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-item-actions']);

            $out .= $this->reorder_buttons($item->get('id'), $item->get('position'), $item->get_max_position(), 'item');

            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-edit"></i>',
                ['class' => 'btn btn-sm btn-outline-secondary mr-2 mod-trainingevaluation-edit-item-btn',
                    'data-item-id' => $item->get('id')]
            );
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-trash"></i>',
                ['class' => 'btn btn-sm btn-outline-secondary mod-trainingevaluation-delete-item-btn',
                    'data-item-id' => $item->get('id')]
            );
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div'); // End of header.

        $out .= html_writer::tag(
            'p',
            format_string($item->get('description')),
            ['class' => 'mod-trainingevaluation-item-description']
        );

        // Item content.
        $out .= html_writer::start_tag(
            'div',
            ['class' => 'mod-trainingevaluation-item-content']
        );

        $type = $item->get_type_instance();
        if ($viewtype == self::VIEW_TYPE_MANAGE) {
            $out .= $type->render_manage_form($trainingevaluation, $item, $userid);
        } else if ($viewtype == self::VIEW_TYPE_EVALUATE) {
            $out .= $type->render_evaluate_form($trainingevaluation, $item, $evaluation);
        } else {
            $out .= $type->render_user_form($trainingevaluation, $item, $evaluation);
        }

        $out .= html_writer::end_tag('div');

        $out .= html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-item-footer']);
        if ($trainingevaluation->showlastmodified && $viewtype != self::VIEW_TYPE_MANAGE) {
            if (
                $response = response::get_record(['itemid' => $item->get('id'), 'userid' => $evaluation->get('userid'),
                    'version' => $evaluation->get('version')])
            ) {
                $lastmodifieduser = $DB->get_record('user', ['id' => $response->get('usermodified')], '*', MUST_EXIST);

                $out .= html_writer::tag(
                    'p',
                    get_string(
                        'lastmodified',
                        'trainingevaluation',
                        ['name' => fullname($lastmodifieduser), 'datetime' => userdate($response->get('timemodified'))]
                    ),
                    ['class' => 'mb-0']
                );
            }
        }
        $out .= html_writer::end_tag('div');

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Generate the reorder buttons for a given section or item.
     *
     * @param int $id
     * @param int $position
     * @param int $maxposition
     * @param string $type
     * @return string
     */
    private function reorder_buttons(int $id, int $position, int $maxposition, string $type): string {
        $out = '';
        if ($position == 0) {
            $out .= html_writer::tag(
                'span',
                '<i class="fa fa-arrow-up"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 disabled mod-trainingevaluation-up-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-arrow-down"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 mod-trainingevaluation-down-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
        } else if ($position == $maxposition) {
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-arrow-up"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 mod-trainingevaluation-up-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
            $out .= html_writer::tag(
                'span',
                '<i class="fa fa-arrow-down"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 disabled mod-trainingevaluation-down-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
        } else {
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-arrow-up"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 mod-trainingevaluation-up-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
            $out .= html_writer::tag(
                'button',
                '<i class="fa fa-arrow-down"></i>',
                ['class' => 'btn btn-sm btn-outline-info mr-2 mod-trainingevaluation-down-' . $type . '-btn',
                    'data-' . $type . '-id' => $id]
            );
        }
        return $out;
    }

    /**
     * Format completion status display.
     *
     * @param int $completedcount Number of completed required items
     * @param int $totalrequired Total number of required items
     * @return string HTML formatted completion status
     */
    public static function format_completion_status(int $completedcount, int $totalrequired): string {
        if ($totalrequired === 0) {
            // No required items, consider complete.
            return html_writer::tag(
                'span',
                get_string('completionstatus:complete', 'trainingevaluation'),
                [
                    'class' => 'badge badge-success',
                ]
            );
        }

        // Calculate completion percentage.
        $percentage = ($totalrequired > 0) ? round(($completedcount / $totalrequired) * 100) : 0;

        // Generate status display.
        if ($completedcount >= $totalrequired) {
            // All required items completed.
            $statustext = get_string('completionstatus:complete', 'trainingevaluation');
            $badgeclass = 'badge badge-success';
            $progressbarclass = 'bg-success';
        } else if ($completedcount > 0) {
            // Partially complete.
            $statustext = get_string('completionstatus:inprogress', 'trainingevaluation') . " ({$completedcount}/{$totalrequired})";
            $badgeclass = 'badge badge-warning';
            $progressbarclass = 'bg-warning';
        } else {
            // Not started.
            $statustext = get_string('completionstatus:notstarted', 'trainingevaluation');
            $badgeclass = 'badge badge-secondary';
            $progressbarclass = 'bg-secondary';
        }

        $progressbar = html_writer::div(
            html_writer::div(
                '',
                "progress-bar {$progressbarclass}",
                [
                    'style' => "width: {$percentage}%",
                    'role' => 'progressbar',
                    'aria-valuenow' => $percentage,
                    'aria-valuemin' => '0',
                    'aria-valuemax' => '100',
                ]
            ),
            'progress'
        );

        return html_writer::div(
            html_writer::tag('span', $statustext, ['class' => $badgeclass]) . $progressbar,
            'mod-trainingevaluation-completion-status'
        );
    }

    /**
     * Render a version selector dropdown for evaluations.
     *
     * @param int $wtid
     * @param evaluation $evaluation
     * @return string
     */
    private function render_version_selector(int $wtid, evaluation $evaluation): string {
        $out = html_writer::start_tag('div', ['class' => 'mod-trainingevaluation-version-selector ml-auto']);

        $versions = evaluation::get_records(
            ['wtid' => $wtid, 'userid' => $evaluation->get('userid')],
            'version',
            'DESC'
        );

        if (count($versions) > 1) {
            $options = [];
            $currentversion = $evaluation->get('version');

            foreach ($versions as $version) {
                $label = get_string('version', 'trainingevaluation') . ' ' . $version->get('version');
                if ($version->get('finalised')) {
                    $label .= ' (' . userdate($version->get('timefinalised'), get_string('strftimedatetime', 'langconfig')) . ')';
                } else {
                    $label .= ' (' . get_string('unfinalised', 'trainingevaluation') . ')';
                }
                if ($version->get('active')) {
                    $label .= ' - ' . get_string('active', 'trainingevaluation');
                }

                $options[$version->get('version')] = $label;
            }

            $select = new \single_select(
                new moodle_url('/mod/trainingevaluation/evaluate.php', [
                    'cmid' => $this->page->cm->id,
                    'userid' => $evaluation->get('userid'),
                ]),
                'version',
                $options,
                $currentversion,
                null,
                'versionselect'
            );
            $select->attributes['autocomplete'] = 'off';
            $select->set_label(get_string('selectversion', 'trainingevaluation'), ['class' => 'mr-2']);
            $select->class = 'mod-trainingevaluation-version-select';

            $out .= $this->output->render($select);
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }
}
