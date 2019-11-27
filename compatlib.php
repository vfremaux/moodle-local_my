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
 * Compatibility functions
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Compatibility functions

function local_my_get_catlist($capability = '') {
    if (empty($capability)) {
        $capability = 'moodle/course:create';
    }
    $mycatlist = \coursecat::make_categories_list('moodle/course:create');
    return $mycatlist;
}

function local_get_category($catid) {
    return \coursecat::get($course->category);
}

function local_get_course_list($course) {
    return new \course_in_list($course);
}