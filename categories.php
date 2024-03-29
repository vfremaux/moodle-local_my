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
 * Lists the course categories
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once("../../config.php");
require_once($CFG->dirroot. '/course/lib.php');

$categoryid = optional_param('categoryid', 0, PARAM_INT); // Category id
$basecategoryid = optional_param('basecategoryid', 0, PARAM_INT); // Top Category id
$site = get_site();

if ($categoryid) {
    $PAGE->set_category_by_id($categoryid);
    $PAGE->set_url(new moodle_url('/local/my/categories.php', array('categoryid' => $categoryid, 'basecategoryid' => $basecategoryid)));
    $PAGE->set_pagetype('course-index-category');
    // And the object has been loaded for us no need for another DB call
    $category = $PAGE->category;
} else {
    // Check if there is only one category, if so use that.
    if (core_course_category::count_all() == 1) {
        $category = core_course_category::get_default();

        $categoryid = $category->id;
        $PAGE->set_category_by_id($categoryid);
        $PAGE->set_pagetype('course-index-category');
    } else {
        $PAGE->set_context(context_system::instance());
    }

    $PAGE->set_url('/local/my/categories.php');
}

$PAGE->set_pagelayout('admin');
$renderer = $PAGE->get_renderer('local_my');

if ($CFG->forcelogin) {
    require_login();
}

if ($categoryid && !$category->visible && !has_capability('moodle/category:viewhiddencategories', $PAGE->context)) {
    throw new moodle_exception('unknowncategory');
}

$PAGE->set_heading($site->fullname);
$PAGE->category->basecategoryid = $basecategoryid;
$PAGE->navbar->add(get_string('categories'));
$params = ['categoryid' => $categoryid, 'basecategoryid' => $basecategoryid];
$PAGE->navbar->add(format_string($category->name), new moodle_url('/local/my/categories.php', $params), 'misc');
$content = $renderer->course_category($PAGE->category);

echo $OUTPUT->header();
echo $OUTPUT->skip_link_target();
echo $content;

// Trigger event, course category viewed.
$eventparams = array('context' => $PAGE->context, 'objectid' => $categoryid);
$event = \core\event\course_category_viewed::create($eventparams);
$event->trigger();

echo $OUTPUT->footer();
