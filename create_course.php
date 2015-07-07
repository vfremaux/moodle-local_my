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
require('../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

$context = context_system::instance();

require_login();

$titlestr = get_string('newcourse', 'local_my');

// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/local/my/create_course.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->set_title($titlestr);
$PAGE->set_heading($titlestr);
$PAGE->navbar->add(get_string('coursecreation', 'local_my'));
$PAGE->navbar->add(get_string('standardcreation', 'local_my'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('choosecategory', 'local_my'));

$displaylist = coursecat::make_categories_list('moodle/course:create');

$table = new html_table();
$table->head = array(get_string('mycategories', 'local_my'));
$table->align = array('left');
$table->width = '70%';
$table->size = array('100%');

foreach ($displaylist as $cid => $cat) {
    $linkurl = new moodle_url('/course/edit.php', array('category' => $cid, 'returnto' => 'category'));
    $link = '<a href="'.$linkurl.'">'.format_string($cat).'</a>';
    $table->data[] = array($link);
}

echo '<center>';
echo html_writer::table($table);
echo '</center>';

echo $OUTPUT->footer();
