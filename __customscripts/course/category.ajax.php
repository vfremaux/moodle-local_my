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
 * Helps moodle-course-categoryexpander to serve AJAX requests
 *
 * @see core_course_renderer::coursecat_include_js()
 * @see core_course_renderer::coursecat_ajax()
 *
 * @package   core
 * @copyright 2013 Andrew Nicols
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

// require_once(__DIR__ . '/../config.php');

if ($CFG->forcelogin) {
    require_login();
}

$PAGE->set_context(context_system::instance());

// CHANGE+.
// Test if we have a basecategory and diverts to local_my renderer override.
$basecategoryid = optional_param('basecategoryid', false, PARAM_INT);

if ($basecategoryid) {
    $courserenderer = $PAGE->get_renderer('local_my');
    $courserenderer->set_basecategoryid($basecategoryid);
} else {
    $courserenderer = $PAGE->get_renderer('core', 'course');
}
// CHANGE-.

echo json_encode($courserenderer->coursecat_ajax());
die;