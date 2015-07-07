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
 * My Moodle -- a user's personal dashboard
 *
 * this screen allows choosing the category for creating a course from within
 * the categories i am owner of.
 *
 * @package    local
 * @subpackage my
 * @reauthor   Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

$context = context_system::instance();

require_login();

$titlestr = get_string('enrollablecourses', 'local_my');

// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/local/my/enrollable_course.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->set_title($titlestr);
$PAGE->set_heading($titlestr);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('choosecoursetoenrollin', 'local_my'));

$courses = local_get_enrollable_courses();
if (empty($courses)) {
    return;
}

$options['printifempty'] = 0;
$options['withcats'] = 2;

echo '<center>';
echo '<div class="block" style="width:80%">';
echo local_my_print_courses('availablecourses', $courses, $options, true);
echo '</div>';

echo $OUTPUT->single_button(new moodle_url('/my/index.php'), get_string('backtohome', 'local_my'));

echo '</center>';
echo $OUTPUT->footer();