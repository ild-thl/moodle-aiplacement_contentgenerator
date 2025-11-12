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
 * Callback implementations for Generate content placement
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Insert a link to the secondary navigation of a course.
 *
 * @param navigation_node $navigation The settings navigation object
 * @param stdClass $course The course
 * @param context $context Course context
 */
function aiplacement_contentgenerator_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (!isloggedin() || isguestuser() || !has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
        return;
    }

    $navigation->add(
        get_string('generatecontent', 'aiplacement_contentgenerator'),
        new moodle_url('/ai/placement/contentgenerator/select_content.php', ['courseid' => $course->id]),
        navigation_node::COURSE_INDEX_PAGE,
    );
}